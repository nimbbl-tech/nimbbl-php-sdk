# Publishing the Nimbbl PHP SDK

This guide covers how to publish the Nimbbl PHP SDK to Packagist and use it in your applications.

## 📦 Prerequisites

1. **Packagist Account**: Create an account at [packagist.org](https://packagist.org)
2. **GitHub Repository**: The SDK should be in a GitHub repository
3. **Composer**: Ensure Composer is installed
4. **Git**: Version control system

## 🚀 Publishing to Packagist

### Step 1: Prepare the Repository

Ensure your repository is ready:

```bash
# Navigate to SDK directory
cd /Users/sandeepyadav/Downloads/sdks/nimbbl-php-sdk

# Ensure all changes are committed
git status

# Tag the version (if not already tagged)
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

### Step 2: Verify composer.json

Ensure `composer.json` is properly configured:

```json
{
    "name": "nimbbl/nimbbl-sdk",
    "description": "Nimbbl PHP client library",
    "type": "library",
    "license": "MIT",
    "authors": [{
        "name": "Harish Patel",
        "email": "harish@logicloop.io"
    }],
    "minimum-stability": "stable",
    "require": {
        "php": ">=7.4",
        "rmccue/requests": "v1.8.0",
        "ext-json": "*"
    },
    "autoload": {
        "psr-4": {
            "Nimbbl\\Api\\": "src/"
        }
    }
}
```

**Key Points:**
- `name`: Must be in format `vendor/package-name`
- `description`: Clear description of the package
- `license`: MIT (or your chosen license)
- `minimum-stability`: Should be "stable" for production releases
- `autoload`: PSR-4 autoloading configured correctly

### Step 3: Create GitHub Release

1. Go to your GitHub repository
2. Click "Releases" → "Create a new release"
3. Tag version: `v1.0.0` (or your version)
4. Release title: `v1.0.0`
5. Description: Release notes
6. Click "Publish release"

### Step 4: Submit to Packagist

1. **Login to Packagist**: Go to [packagist.org](https://packagist.org) and login
2. **Submit Package**: Click "Submit" in the top menu
3. **Enter Repository URL**: 
   ```
   https://github.com/your-username/nimbbl-php-sdk
   ```
4. **Check Package**: Packagist will validate the package
5. **Submit**: Click "Submit" to add the package

### Step 5: Enable Auto-Update (Recommended)

1. Go to your package page on Packagist
2. Click "Settings"
3. Enable "Auto-update"
4. Add a GitHub webhook (if not already added):
   - Go to GitHub repository → Settings → Webhooks
   - Add webhook: `https://packagist.org/api/github?username=YOUR_PACKAGIST_USERNAME`
   - Secret: Your Packagist API token

## 📝 Version Management

### Semantic Versioning

Follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (1.0.0): Breaking changes
- **MINOR** (1.1.0): New features, backward compatible
- **PATCH** (1.0.1): Bug fixes, backward compatible

### Creating a New Release

```bash
# 1. Update version in composer.json (if needed)
# 2. Update CHANGELOG.md
# 3. Commit changes
git add .
git commit -m "Release v1.0.1"
git push

# 4. Create and push tag
git tag -a v1.0.1 -m "Release version 1.0.1"
git push origin v1.0.1

# 5. Create GitHub release (via web interface)
# Packagist will auto-update if webhook is configured
```

## 🔄 Updating the Package

### Manual Update

If auto-update is not enabled:

1. Go to your package page on Packagist
2. Click "Update" button
3. Packagist will fetch the latest version

### Auto-Update via Webhook

If webhook is configured:
- Push a new tag → GitHub webhook → Packagist auto-updates

## 📦 Installing from Packagist

Once published, users can install via Composer:

```bash
composer require nimbbl/nimbbl-sdk
```

## 🏗️ Local Development Setup

### Using Local Package During Development

If you want to test the package locally before publishing:

```bash
# In your test project's composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "../nimbbl-php-sdk"
        }
    ],
    "require": {
        "nimbbl/nimbbl-sdk": "*"
    }
}

# Then run
composer update
```

### Using Git Repository Directly

```bash
# In your test project's composer.json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/your-username/nimbbl-php-sdk"
        }
    ],
    "require": {
        "nimbbl/nimbbl-sdk": "dev-master"
    }
}
```

## ✅ Pre-Publishing Checklist

Before publishing, ensure:

- [ ] `composer.json` is valid (`composer validate`)
- [ ] All tests pass (`php tests/run-all-tests.php`)
- [ ] Examples work correctly
- [ ] README.md is up to date
- [ ] CHANGELOG.md is updated
- [ ] Version is tagged in Git
- [ ] GitHub release is created
- [ ] No sensitive data in repository
- [ ] `.gitignore` excludes vendor/, logs/, config.php

## 🧪 Testing the Package

### Validate composer.json

```bash
composer validate
```

### Test Installation

```bash
# Create a test project
mkdir test-install
cd test-install
composer init

# Require your package
composer require nimbbl/nimbbl-sdk

# Test usage
php -r "require 'vendor/autoload.php'; echo 'SDK loaded successfully';"
```

## 📋 Package Information

### Current Package Details

- **Package Name**: `nimbbl/nimbbl-sdk`
- **Type**: Library
- **License**: MIT
- **PHP Version**: >= 7.4
- **Dependencies**: 
  - `rmccue/requests`: v1.8.0
  - `ext-json`: Required extension

### Package Structure

```
nimbbl-php-sdk/
├── composer.json          # Package definition
├── README.md              # Package documentation
├── CHANGELOG.md           # Version history
├── LICENSE                # License file
├── src/                   # Source code
│   ├── Api/              # Main API client
│   ├── Order/            # Orders API
│   ├── Payment/          # Payments API
│   └── ...               # Other APIs
├── example/               # Example applications
└── tests/                 # Test suite
```

## 🔐 Security Considerations

1. **Never commit credentials**: Use `.gitignore` for `config.php`
2. **Review dependencies**: Regularly update dependencies for security patches
3. **Tag releases**: Always tag releases for version tracking
4. **Sign tags**: Consider GPG signing tags for authenticity

## 📞 Support

For publishing issues:
- [Packagist Documentation](https://packagist.org/about)
- [Composer Documentation](https://getcomposer.org/doc/)
- Contact: support@nimbbl.biz

## 🔗 Related Documentation

- [README.md](./README.md) - SDK documentation
- [example/README.md](./example/README.md) - Example app guide
- [HOW_TO_RUN.md](./HOW_TO_RUN.md) - Setup and usage instructions

