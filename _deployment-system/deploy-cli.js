#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const yaml = require('js-yaml');
const { program } = require('commander');
const ftp = require('basic-ftp');
const fetch = require('node-fetch');
const glob = require('fast-glob');
const { execSync } = require('child_process');

// CLI Setup
program
  .version('1.0.0')
  .description('Robust deployment CLI for web projects')
  .option('-p, --project <project>', 'Project to deploy', 'web')
  .option('-t, --target <target>', 'Deployment target', 'production')  
  .option('-P, --parts <parts>', 'Parts to deploy (comma-separated)', 'default')
  .option('-m, --method <method>', 'Deployment method (ftp|copy|ssh)')
  .option('-d, --dry-run', 'Show what would be deployed without uploading')
  .option('-f, --force', 'Force rebuild and overwrite')
  .option('-b, --build', 'Run build before deployment')
  .option('-v, --verbose', 'Verbose logging');

// Global state
let config = null;
let manifest = {
  timestamp: new Date().toISOString(),
  project: '',
  target: '',
  parts: [],
  files: [],
  verification: null,
  success: false
};

// Utility functions
function log(level, message) {
  const timestamp = new Date().toISOString().substring(11, 19);
  const colors = {
    ERROR: '\x1b[31m',
    WARN: '\x1b[33m', 
    SUCCESS: '\x1b[32m',
    INFO: '\x1b[36m',
    STEP: '\x1b[35m',
    RESET: '\x1b[0m'
  };
  
  console.log(`${colors[level] || ''}[${timestamp}] [${level}] ${message}${colors.RESET}`);
}

function loadConfig() {
  const configPath = path.join(__dirname, 'deployment-config.yaml');
  if (!fs.existsSync(configPath)) {
    throw new Error(`Configuration file not found: ${configPath}`);
  }
  
  let configContent = fs.readFileSync(configPath, 'utf8');
  
  // Replace environment variables
  configContent = configContent.replace(/\$\{([^}]+)\}/g, (match, varName) => {
    return process.env[varName] || match;
  });
  
  const parsedConfig = yaml.load(configContent);
  log('SUCCESS', `Configuration loaded from ${configPath}`);
  return parsedConfig;
}

function resolveProjectFiles(projectConfig, parts, excludes) {
  const files = [];
  const partsArray = parts.split(',').map(p => p.trim());
  
  log('STEP', `Resolving files for parts: ${partsArray.join(', ')}`);
  
  for (const partName of partsArray) {
    const part = projectConfig.parts[partName];
    if (!part) {
      throw new Error(`Part '${partName}' not found in project configuration`);
    }
    
    const basePath = projectConfig.localBuild || projectConfig.localSource;
    if (!fs.existsSync(basePath)) {
      throw new Error(`Source path not found: ${basePath}`);
    }
    
    // Get included files
    const includePatterns = part.include.map(pattern => 
      path.join(basePath, pattern).replace(/\\/g, '/')
    );
    
    const matchedFiles = glob.sync(includePatterns, { 
      dot: false,
      ignore: excludes.map(ex => path.join(basePath, ex).replace(/\\/g, '/'))
    });
    
    for (const filePath of matchedFiles) {
      const relativePath = path.relative(basePath, filePath);
      const remotePath = path.join(part.remotePath || '/', relativePath).replace(/\\/g, '/');
      
      files.push({
        localPath: filePath,
        relativePath: relativePath,
        remotePath: remotePath,
        part: partName,
        size: fs.statSync(filePath).size
      });
    }
    
    log('INFO', `Part '${partName}': found ${matchedFiles.length} files`);
  }
  
  return files;
}

function validatePathMappings(projectConfig, targetConfig) {
  log('STEP', 'Validating path mappings...');
  
  // Check source directory (for building)
  const localSource = projectConfig.localSource;
  if (!fs.existsSync(localSource)) {
    throw new Error(`Local source path not found: ${localSource}`);
  }
  log('INFO', `✓ Local source exists: ${localSource}`);
  
  // Check build directory (for deployment) - this might not exist if build hasn't run yet
  const buildPath = projectConfig.localBuild || localSource;
  if (fs.existsSync(buildPath)) {
    log('INFO', `✓ Build path exists: ${buildPath}`);
  } else {
    log('WARN', `Build path not found: ${buildPath} (will be created during build)`);
  }
  
  if (targetConfig.method === 'ftp') {
    log('INFO', `✓ Remote target: ${targetConfig.ftp.host}${targetConfig.remoteRoot}`);
  } else if (targetConfig.method === 'copy') {
    log('INFO', `✓ Local target: ${targetConfig.localPath}`);
  }
  
  log('SUCCESS', 'Path mappings validated');
}

async function runBuild(projectConfig) {
  if (!projectConfig.buildCommand) {
    log('INFO', 'No build command configured');
    return true;
  }
  
  const buildDir = path.join(projectConfig.localSource, 'build');
  if (fs.existsSync(buildDir) && !program.opts().force) {
    log('INFO', 'Build directory exists, skipping build (use --force to rebuild)');
    return true;
  }
  
  log('STEP', `Running build: ${projectConfig.buildCommand}`);
  
  try {
    process.chdir(projectConfig.localSource);
    execSync(projectConfig.buildCommand, { stdio: 'inherit' });
    log('SUCCESS', 'Build completed successfully');
    return true;
  } catch (error) {
    log('ERROR', `Build failed: ${error.message}`);
    return false;
  }
}

function createVerificationFile(projectConfig, targetConfig) {
  if (!projectConfig.verify?.enabled) {
    return null;
  }
  
  const timestamp = Date.now();
  const fileName = projectConfig.verify.fileTemplate.replace('{timestamp}', timestamp);
  const content = `<!DOCTYPE html>
<html>
<head>
    <title>Deployment Verification</title>
</head>
<body>
    <h1>Deployment Verification</h1>
    <p>Deployed at: ${new Date().toISOString()}</p>
    <p>Timestamp: ${timestamp}</p>
    <p>Target: ${program.opts().target}</p>
    <p>Project: ${program.opts().project}</p>
</body>
</html>`;
  
  const buildPath = projectConfig.localBuild || projectConfig.localSource;
  const filePath = path.join(buildPath, fileName);
  
  fs.writeFileSync(filePath, content);
  log('SUCCESS', `Verification file created: ${fileName}`);
  
  const url = projectConfig.verify.urlTemplate
    .replace('{domain}', targetConfig.domain)
    .replace('{file}', fileName);
    
  return {
    fileName,
    filePath,
    url,
    content,
    timestamp
  };
}

async function testFTPConnection(ftpConfig) {
  log('STEP', `Testing FTP connection to ${ftpConfig.host}...`);
  
  const client = new ftp.Client();
  client.ftp.timeout = ftpConfig.timeout || 30000;
  
  try {
    await client.access({
      host: ftpConfig.host,
      user: ftpConfig.user,
      password: ftpConfig.password,
      secure: false
    });
    
    log('SUCCESS', 'FTP connection successful');
    await client.close();
    return true;
  } catch (error) {
    log('ERROR', `FTP connection failed: ${error.message}`);
    return false;
  }
}

async function uploadViaFTP(files, ftpConfig, remoteRoot) {
  log('STEP', `Uploading ${files.length} files via FTP...`);
  
  const client = new ftp.Client();
  client.ftp.timeout = ftpConfig.timeout || 30000;
  
  try {
    await client.access({
      host: ftpConfig.host,
      user: ftpConfig.user,
      password: ftpConfig.password,
      secure: false
    });
    
    let successCount = 0;
    let failCount = 0;
    
    for (const file of files) {
      try {
        const remotePath = path.join(remoteRoot, file.remotePath).replace(/\\/g, '/');
        const remoteDir = path.dirname(remotePath);
        
        // Ensure directory exists
        try {
          await client.ensureDir(remoteDir);
        } catch (dirError) {
          // Directory might already exist
        }
        
        await client.uploadFrom(file.localPath, remotePath);
        successCount++;
        
        if (program.opts().verbose) {
          log('INFO', `✓ ${file.relativePath} → ${remotePath}`);
        }
      } catch (error) {
        failCount++;
        log('ERROR', `✗ ${file.relativePath}: ${error.message}`);
      }
    }
    
    await client.close();
    
    log('SUCCESS', `FTP upload completed: ${successCount} success, ${failCount} failed`);
    return failCount === 0;
    
  } catch (error) {
    log('ERROR', `FTP upload failed: ${error.message}`);
    return false;
  }
}

function deployViaCopy(files, targetPath) {
  log('STEP', `Copying ${files.length} files to ${targetPath}...`);
  
  if (!fs.existsSync(targetPath)) {
    fs.mkdirSync(targetPath, { recursive: true });
  }
  
  let successCount = 0;
  let failCount = 0;
  
  for (const file of files) {
    try {
      const destPath = path.join(targetPath, file.relativePath);
      const destDir = path.dirname(destPath);
      
      if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true });
      }
      
      fs.copyFileSync(file.localPath, destPath);
      successCount++;
      
      if (program.opts().verbose) {
        log('INFO', `✓ ${file.relativePath} → ${destPath}`);
      }
    } catch (error) {
      failCount++;
      log('ERROR', `✗ ${file.relativePath}: ${error.message}`);
    }
  }
  
  log('SUCCESS', `Local copy completed: ${successCount} success, ${failCount} failed`);
  return failCount === 0;
}

async function verifyDeployment(verificationFile, targetConfig) {
  if (!verificationFile) {
    log('INFO', 'No verification configured');
    return true;
  }
  
  log('STEP', 'Verifying deployment...');
  
  try {
    const response = await fetch(verificationFile.url, {
      timeout: config.defaults.verification.httpTimeout || 10000
    });
    
    if (response.ok) {
      const content = await response.text();
      if (content.includes(verificationFile.timestamp.toString())) {
        log('SUCCESS', `✓ Verification URL accessible: ${verificationFile.url}`);
        return true;
      } else {
        log('ERROR', `✗ Verification content mismatch at: ${verificationFile.url}`);
        return false;
      }
    } else {
      log('ERROR', `✗ HTTP ${response.status}: ${verificationFile.url}`);
      return false;
    }
  } catch (error) {
    log('ERROR', `✗ Verification failed: ${error.message}`);
    return false;
  }
}

function saveManifest() {
  const manifestPath = path.join(__dirname, 'manifests', `deploy-${manifest.timestamp.replace(/[:.]/g, '-')}.json`);
  fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));
  log('SUCCESS', `Deployment manifest saved: ${path.basename(manifestPath)}`);
}

// Main deployment function
async function main() {
  try {
    program.parse();
    const options = program.opts();
    
    // Load configuration
    config = loadConfig();
    
    // Setup manifest
    manifest.project = options.project;
    manifest.target = options.target;
    manifest.parts = options.parts.split(',').map(p => p.trim());
    
    log('INFO', `Starting deployment: ${options.project} → ${options.target} (${options.parts})`);
    
    // Get project and target config
    const projectConfig = config.projects[options.project];
    if (!projectConfig) {
      throw new Error(`Project '${options.project}' not found in configuration`);
    }
    
    const targetConfig = config.targets[options.target];
    if (!targetConfig) {
      throw new Error(`Target '${options.target}' not found in configuration`);
    }
    
    // Validate path mappings early
    validatePathMappings(projectConfig, targetConfig);
    
    // Run build if requested
    if (options.build && !await runBuild(projectConfig)) {
      throw new Error('Build failed');
    }
    
    // Resolve files to deploy
    const files = resolveProjectFiles(projectConfig, options.parts, config.defaults.excludes);
    manifest.files = files.map(f => ({ 
      relativePath: f.relativePath, 
      remotePath: f.remotePath, 
      size: f.size, 
      part: f.part 
    }));
    
    if (files.length === 0) {
      throw new Error('No files found to deploy');
    }
    
    log('INFO', `Found ${files.length} files to deploy (${files.reduce((sum, f) => sum + f.size, 0)} bytes)`);
    
    // Create verification file
    const verificationFile = createVerificationFile(projectConfig, targetConfig);
    if (verificationFile) {
      files.push({
        localPath: verificationFile.filePath,
        relativePath: verificationFile.fileName,
        remotePath: `/${verificationFile.fileName}`,
        part: 'verification',
        size: fs.statSync(verificationFile.filePath).size
      });
    }
    
    // Dry run mode
    if (options.dryRun) {
      log('INFO', '=== DRY RUN MODE ===');
      log('INFO', `Would deploy to: ${targetConfig.method} ${targetConfig.ftp?.host || targetConfig.localPath}`);
      files.forEach(file => {
        log('INFO', `  ${file.relativePath} → ${file.remotePath} (${file.size} bytes)`);
      });
      process.exit(0);
    }
    
    // Deploy files
    let deploySuccess = false;
    const method = options.method || targetConfig.method;
    
    if (method === 'ftp') {
      if (!await testFTPConnection(targetConfig.ftp)) {
        throw new Error('FTP connection test failed');
      }
      deploySuccess = await uploadViaFTP(files, targetConfig.ftp, targetConfig.remoteRoot);
    } else if (method === 'copy') {
      deploySuccess = deployViaCopy(files, targetConfig.localPath);
    } else {
      throw new Error(`Unsupported deployment method: ${method}`);
    }
    
    if (!deploySuccess) {
      throw new Error('File deployment failed');
    }
    
    // Verify deployment
    const verifySuccess = await verifyDeployment(verificationFile, targetConfig);
    manifest.verification = verificationFile ? {
      url: verificationFile.url,
      success: verifySuccess
    } : null;
    
    // Clean up verification file if successful
    if (verificationFile && verifySuccess && config.defaults.verification.cleanupVerificationFile) {
      try {
        fs.unlinkSync(verificationFile.filePath);
        log('INFO', 'Verification file cleaned up');
      } catch (error) {
        log('WARN', `Failed to clean up verification file: ${error.message}`);
      }
    }
    
    manifest.success = verifySuccess;
    saveManifest();
    
    if (verifySuccess) {
      log('SUCCESS', '🚀 Deployment completed successfully!');
      if (verificationFile) {
        log('INFO', `Verify at: ${verificationFile.url}`);
      }
      process.exit(0);
    } else {
      throw new Error('Deployment verification failed');
    }
    
  } catch (error) {
    log('ERROR', error.message);
    manifest.success = false;
    manifest.error = error.message;
    saveManifest();
    process.exit(1);
  }
}

// Run if called directly
if (require.main === module) {
  main();
}

module.exports = { main, loadConfig, resolveProjectFiles };
