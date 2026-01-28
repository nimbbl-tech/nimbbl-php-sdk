<?php

// Include Requests only if not already defined
if (class_exists('Requests') === false) {
    require_once __DIR__ . '/libs/Requests-1.8/library/Requests.php';
}

try {
    Requests::register_autoloader();
    if (version_compare(Requests::VERSION, '1.8.0') === -1) {
        throw new Exception('Requests class found but did not match');
    }
} catch (\Exception $e) {
    throw new Exception('Requests class found but did not match');
}

spl_autoload_register(function ($class) {
    // project-specific namespace prefix
    $prefix = 'Nimbbl\Api';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/src/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // First, try PSR-4 style: direct mapping (e.g., Nimbbl\Api\Order -> src/Order.php)
    // This handles classes that follow PSR-4 naming (namespace matches directory structure)
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
        return;
    }

    // If not found, search in subdirectories for classes in Nimbbl\Api namespace
    // but located in subdirectories (e.g., JsonKeys in src/Common/JsonKeys.php)
    // This matches composer's classmap behavior for non-PSR-4 classes
    $subdirs = ['Common', 'RestClient', 'Log', 'Services', 'Exception'];
    foreach ($subdirs as $subdir) {
        $subdir_file = $base_dir . $subdir . '/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($subdir_file)) {
            require $subdir_file;
            return;
        }
    }
});
