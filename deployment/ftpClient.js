const ftp = require('basic-ftp');
const logger = require('./logger');

class FTPClientWrapper {
  constructor(config) {
    this.config = config || {};
    this.client = new ftp.Client();
  // Allow turning on verbose FTP traces with environment variable FTP_VERBOSE=1
  this.client.ftp.verbose = !!process.env.FTP_VERBOSE;
  }

  async connect() {
    logger.info(`Connecting to FTP ${this.config.host}`);
    await this.client.access(this.config);
    logger.info('FTP connected');
  }

  async ensureDir(dir) {
    await this.client.ensureDir(dir);
  }

  async upload(local, remote) {
    logger.info(`Uploading ${local} -> ${remote}`);
    await this.client.uploadFrom(local, remote);
  }

  async uploadDir(localDir, remoteDir) {
    logger.info(`Uploading directory ${localDir} -> ${remoteDir}`);
    await this.client.ensureDir(remoteDir);
    await this.client.uploadFromDir(localDir);
  }

  async list(remoteDir) {
    return await this.client.list(remoteDir);
  }

  async close() {
    this.client.close();
  }
}

module.exports = FTPClientWrapper;
