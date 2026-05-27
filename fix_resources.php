<?php
// Script to fix Filament resource properties for PHP 8.5 compatibility

$dir = __DIR__ . '/app/Filament/Resources/Panel';
$files = glob($dir . '/*Resource.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    $iconValue = null;
    $groupValue = null;
    $sortValue = null;
    
    // Extract values - match any format of property declaration
    if (preg_match("/\\\$s*navigationIcon\s*=\s*'([^']+)'/", $content, $m)) {
        $iconValue = $m[1];
    }
    if (preg_match("/\\\$s*navigationGroup\s*=\s*'([^']+)'/", $content, $m)) {
        $groupValue = $m[1];
    }
    if (preg_match("/\\\$s*navigationSort\s*=\s*(\d+)/", $content, $m)) {
        $sortValue = $m[1];
    }
    
    // Remove navigationIcon property lines (any format)
    $content = preg_replace("/^\s*protected static [^\n]*\$navigationIcon[^\n]*\n/m", '', $content);
    // Remove navigationGroup property lines
    $content = preg_replace("/^\s*protected static [^\n]*\$navigationGroup[^\n]*\n/m", '', $content);
    // Remove navigationSort property lines
    $content = preg_replace("/^\s*protected static [^\n]*\$navigationSort[^\n]*\n/m", '', $content);
    
    // Remove any getter methods we previously added
    $content = preg_replace("/\n    public static function getNavigationIcon\(\)[^\}]+\}/", '', $content);
    $content = preg_replace("/\n    public static function getNavigationGroup\(\)[^\}]+\}/", '', $content);
    $content = preg_replace("/\n    public static function getNavigationSort\(\)[^\}]+\}/", '', $content);
    
    // Build method overrides
    $methods = '';
    if ($iconValue !== null) {
        $methods .= "\n    public static function getNavigationIcon(): string | \\BackedEnum | null\n    {\n        return '$iconValue';\n    }\n";
    }
    if ($groupValue !== null) {
        $methods .= "\n    public static function getNavigationGroup(): string | \\UnitEnum | null\n    {\n        return '$groupValue';\n    }\n";
    }
    if ($sortValue !== null) {
        $methods .= "\n    public static function getNavigationSort(): ?int\n    {\n        return $sortValue;\n    }\n";
    }
    
    // Insert methods before the first existing method
    if ($methods) {
        $content = preg_replace(
            "/(\n    public static function (getModelLabel|form|table|getNavigationLabel|getPluralModelLabel))/",
            $methods . "\$1",
            $content,
            1
        );
    }
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Fixed: " . basename($file) . " (icon=$iconValue, group=$groupValue, sort=$sortValue)\n";
    } else {
        echo "No changes: " . basename($file) . "\n";
    }
}

// Also fix Shield RoleResource
$shieldFile = __DIR__ . '/app/Filament/Resources/Shield/RoleResource.php';
if (file_exists($shieldFile)) {
    $content = file_get_contents($shieldFile);
    $original = $content;
    
    $iconValue = null;
    $groupValue = null;
    
    if (preg_match("/\\\$s*navigationIcon\s*=\s*'([^']+)'/", $content, $m)) {
        $iconValue = $m[1];
    }
    if (preg_match("/\\\$s*navigationGroup\s*=\s*'([^']+)'/", $content, $m)) {
        $groupValue = $m[1];
    }
    
    $content = preg_replace("/^\s*protected static [^\n]*\$navigationIcon[^\n]*\n/m", '', $content);
    $content = preg_replace("/^\s*protected static [^\n]*\$navigationGroup[^\n]*\n/m", '', $content);
    $content = preg_replace("/\n    public static function getNavigationIcon\(\)[^\}]+\}/", '', $content);
    $content = preg_replace("/\n    public static function getNavigationGroup\(\)[^\}]+\}/", '', $content);
    
    $methods = '';
    if ($iconValue !== null) {
        $methods .= "\n    public static function getNavigationIcon(): string | \\BackedEnum | null\n    {\n        return '$iconValue';\n    }\n";
    }
    if ($groupValue !== null) {
        $methods .= "\n    public static function getNavigationGroup(): string | \\UnitEnum | null\n    {\n        return '$groupValue';\n    }\n";
    }
    
    if ($methods) {
        $content = preg_replace(
            "/(\n    public static function (getModelLabel|form|table|getNavigationLabel))/",
            $methods . "\$1",
            $content,
            1
        );
    }
    
    if ($content !== $original) {
        file_put_contents($shieldFile, $content);
        echo "Fixed: Shield/RoleResource.php (icon=$iconValue, group=$groupValue)\n";
    } else {
        echo "No changes: Shield/RoleResource.php\n";
    }
}

echo "\nDone!\n";