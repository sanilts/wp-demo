# Elementor Pro Activator

## Description
Elementor Pro Activator is a WordPress plugin that allows users to activate Elementor Pro features without the typical license purchase. This plugin is designed for development and testing purposes only.

## Installation
1. Upload the `elementor-pro-activator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure that Elementor Pro is installed and activated

## Requirements
- WordPress 5.9.0 or higher
- PHP 7.2 or higher
- Elementor Pro plugin installed

## Usage
Once activated, the plugin will automatically set up a valid license for Elementor Pro. No additional configuration is required.

## Disclaimer
This plugin is intended for development and testing environments only. It should not be used on production sites or for commercial purposes. Always respect the licensing terms of Elementor Pro.

## Changelog

### 1.0.0 (2024-08-02)
- Initial release
- Added automatic license activation for Elementor Pro
- Added filter to validate license with Elementor's API

### 1.0.1 (2024-08-02)
- Removed unnecessary admin notices
- Improved code structure and readability
- Implemented network activation support for multisite installations
- Removed unused 'download_file' function

### 1.0.2 (2024-08-05)
- Added external API to download templates

### 1.0.3 (2024-08-08)
- Added all the pro features array for license validation
- Added extra reponses for external API calls

### 1.0.4 (2024-08-14)
- Now require GPL Times auto updater plugin to download premium templates and Kit Library.
- Can import the Elementor Kit Library as well.
- Bug fixes.

### 1.0.5 (2024-08-25)
- Added admin notice to prompt installation of GPL Times auto updater plugin if not installed
- Implemented one-click installer for GPL Times auto updater plugin
- Improved user experience with automatic activation after installation
- Enhanced security with nonce verification for plugin installation process