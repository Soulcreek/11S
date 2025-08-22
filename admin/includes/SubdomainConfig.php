<?php
// File: admin/includes/SubdomainConfig.php
// Description: Configuration for admin.11seconds.de subdomain setup

class SubdomainConfig {
    
    // Detect if we're running on admin subdomain
    public static function isSubdomain() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return strpos($host, 'admin.') === 0;
    }
    
    // Get base URL for admin center
    public static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        
        if (self::isSubdomain()) {
            return $protocol . 'admin.11seconds.de/';
        } else {
            return $protocol . $_SERVER['HTTP_HOST'] . '/admin/';
        }
    }
    
    // Get main site URL
    public static function getMainSiteUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        return $protocol . '11seconds.de/';
    }
    
    // Generate admin URL with proper subdomain handling
    public static function adminUrl($path = '') {
        $baseUrl = self::getBaseUrl();
        
        // Remove leading slash from path if present
        $path = ltrim($path, '/');
        
        // For subdomain, use clean URLs
        if (self::isSubdomain()) {
            switch ($path) {
                case 'dashboard.php':
                    return $baseUrl . 'dashboard/';
                case 'user-management-enhanced.php':
                    return $baseUrl . 'users/';
                case 'security-dashboard.php':
                    return $baseUrl . 'security/';
                case 'settings.php':
                    return $baseUrl . 'settings/';
                case 'question-management.php':
                    return $baseUrl . 'questions/';
                case 'question-generator.php':
                    return $baseUrl . 'generator/';
                case 'backup.php':
                    return $baseUrl . 'backup/';
                case 'setup-database.php':
                    return $baseUrl . 'setup/';
                case 'index.php':
                case '':
                    return $baseUrl;
                default:
                    return $baseUrl . $path;
            }
        } else {
            // Traditional directory-based URLs
            return $baseUrl . $path;
        }
    }
    
    // Check if admin subdomain is configured
    public static function isSubdomainConfigured() {
        // Try to resolve admin.11seconds.de
        $ip = gethostbyname('admin.11seconds.de');
        $mainIp = gethostbyname('11seconds.de');
        
        // If they resolve to the same IP, subdomain is likely configured
        return $ip !== 'admin.11seconds.de' && $ip === $mainIp;
    }
    
    // Get navigation items with proper URLs
    public static function getNavigationItems() {
        return [
            [
                'title' => 'Dashboard',
                'url' => self::adminUrl('dashboard.php'),
                'icon' => 'fas fa-tachometer-alt',
                'description' => 'System overview and statistics'
            ],
            [
                'title' => 'Erweiterte Benutzerverwaltung',
                'url' => self::adminUrl('user-management-enhanced.php'),
                'icon' => 'fas fa-users-cog',
                'description' => 'Professional user management'
            ],
            [
                'title' => 'Sicherheits-Dashboard',
                'url' => self::adminUrl('security-dashboard.php'),
                'icon' => 'fas fa-shield-alt',
                'description' => 'Security monitoring and alerts'
            ],
            [
                'title' => 'Fragen verwalten',
                'url' => self::adminUrl('question-management.php'),
                'icon' => 'fas fa-question-circle',
                'description' => 'Manage quiz questions'
            ],
            [
                'title' => 'KI Fragengenerator',
                'url' => self::adminUrl('question-generator.php'),
                'icon' => 'fas fa-robot',
                'description' => 'AI-powered question generation'
            ],
            [
                'title' => 'Backup & Export',
                'url' => self::adminUrl('backup.php'),
                'icon' => 'fas fa-download',
                'description' => 'Data backup and export'
            ],
            [
                'title' => 'Einstellungen',
                'url' => self::adminUrl('settings.php'),
                'icon' => 'fas fa-cog',
                'description' => 'System configuration'
            ]
        ];
    }
    
    // Generate breadcrumb navigation
    public static function getBreadcrumb($currentPage) {
        $breadcrumb = [
            [
                'title' => 'Admin Center',
                'url' => self::adminUrl(),
                'active' => false
            ]
        ];
        
        $pages = [
            'dashboard.php' => 'Dashboard',
            'user-management-enhanced.php' => 'User Management',
            'security-dashboard.php' => 'Security Dashboard',
            'question-management.php' => 'Questions',
            'question-generator.php' => 'AI Generator',
            'settings.php' => 'Settings',
            'backup.php' => 'Backup'
        ];
        
        if (isset($pages[$currentPage])) {
            $breadcrumb[] = [
                'title' => $pages[$currentPage],
                'url' => self::adminUrl($currentPage),
                'active' => true
            ];
        }
        
        return $breadcrumb;
    }
}

// Auto-detect and set configuration
if (SubdomainConfig::isSubdomain()) {
    // Running on admin.11seconds.de
    define('ADMIN_SUBDOMAIN', true);
    define('ADMIN_BASE_URL', SubdomainConfig::getBaseUrl());
} else {
    // Running on 11seconds.de/admin/
    define('ADMIN_SUBDOMAIN', false);
    define('ADMIN_BASE_URL', SubdomainConfig::getBaseUrl());
}

?>
