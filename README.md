# TYPO3 Extension `page_password` - Protect your pages with a password

![page_password](./Documentation/img/pagpassword.svg)

## Overview

PagePassword provides a simple way to restrict access to specific pages and their sub-pages with password authentication. This extension allows you to create password-protected sections or entire page trees within your TYPO3 website. Perfect for creating member areas, work-in-progress sections, pre-production environments, or any content that requires basic access control at the page level.

## Features

- 🔒 **Page-level protection**: Secure individual pages and their subpages with custom passwords
- 🛡️ **Easy setup**: Simple configuration through TYPO3 backend
- 🎨 **Customizable**: Flexible styling
- 🌐 **Multi-language support**: Works with TYPO3 localization
- ⚡ **Performance optimized**: Minimal impact on site performance

## What's next?
- Logging
- Rate limiter
- Backend module overview

## Requirements

- TYPO3 12.4 LTS or higher
- PHP 8.2 or higher

## Installation

### Via composer
```bash
composer require rovitch/page-password
```
Go to `maintenance` -> `Analyze Database Structure` and apply database changes

## Setup

See [initial setup](Documentation/intital_setup.md)

## Development
```bash
# Install dependencies
make install
```
### Running tests
```bash
# Run all tests
make test
```
```bash
# Run rector and cgl fixes
make fix
```
### Building assets

```bash
# Install dependencies
npm install

# Build css for development
npx tailwindcss -i ./Resources/Private/Css/tailwind.css -o ./Resources/Public/assets/css/main.min.css --watch

# Build css for production
npx tailwindcss -i ./Resources/Private/Css/tailwind.css -o ./Resources/Public/assets/css/main.min.css --minify

# Build typescript
webpack --config webpack.config.js
```
