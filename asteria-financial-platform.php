<?php
/**
 * Plugin Name: Asteria Financial Platform
 * Plugin URI: https://github.com/ildrm/asteria-financial-platform
 * Description: Self-contained financial intelligence workspace with synthetic markets, analytics, portfolios, risk, and paper trading.
 * Version:     0.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author:      Shahin Ilderemi
 * Author URI:  https://ildrm.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: asteria-financial-platform
 * Domain Path: /languages
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('ASTERIA_FP_VERSION', '0.2.0');
define('ASTERIA_FP_FILE', __FILE__);
define('ASTERIA_FP_DIR', __DIR__);

$autoload = __DIR__ . '/vendor/autoload.php';
require is_readable($autoload) ? $autoload : __DIR__ . '/autoload.php';

$plugin = new Asteria\FinancialPlatform\Plugin();
register_activation_hook(__FILE__, [$plugin, 'activate']);
register_deactivation_hook(__FILE__, [$plugin, 'deactivate']);
$plugin->boot();
