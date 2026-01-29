# Packagist Publishing Guide

This guide walks you through publishing Pop Framework to Packagist so users can install it via:

```bash
composer create-project pop-framework/pop my-app
```

## Prerequisites Checklist

Before publishing to Packagist, ensure you have:

- [x] GitHub repository set up and pushed
- [x] composer.json properly configured
- [x] README.md at project root
- [x] CHANGELOG.md created
- [x] LICENSE file present
- [ ] Update placeholders in composer.json
- [ ] Update placeholders in README.md
- [ ] Commit and push all changes
- [ ] Create a Git tag for version 1.0.0

## Step 1: Update Placeholders

### 1.1 Update composer.json

Open [composer.json](../composer.json) and replace:

```json
"homepage": "https://github.com/YOUR_USERNAME/pop",
"authors": [
    {
        "name": "YOUR_NAME",
        "email": "your.email@example.com"
    }
],
"support": {
    "issues": "https://github.com/YOUR_USERNAME/pop/issues",
    "source": "https://github.com/YOUR_USERNAME/pop",
    "docs": "https://github.com/YOUR_USERNAME/pop#readme"
}
```

With your actual information:
- Replace `YOUR_USERNAME` with your GitHub username
- Replace `YOUR_NAME` with your name or organization name
- Replace `your.email@example.com` with your email

### 1.2 Update README.md

Open [README.md](../README.md) and replace:
- All instances of `YOUR_USERNAME` with your GitHub username
- Update support URLs

### 1.3 Update CHANGELOG.md

Open [CHANGELOG.md](../CHANGELOG.md) and replace:
- `YOUR_USERNAME` in the comparison URLs at the bottom
- Set the release date for version 1.0.0

### 1.4 Update CONTRIBUTING.md

Open [CONTRIBUTING.md](../CONTRIBUTING.md) and replace:
- `YOUR_USERNAME` in GitHub URLs
- `ORIGINAL_OWNER` in upstream remote URL

### 1.5 Update LICENSE (Optional)

Open [LICENSE](../LICENSE) and consider updating:
- Copyright holder from "Technology" to your name/organization

## Step 2: Commit Your Changes

```bash
# Check git status
git status

# Add all files
git add .

# Commit with a clear message
git commit -m "chore: Prepare for Packagist publication

- Update composer.json with package metadata
- Add README.md with installation guide
- Add CHANGELOG.md for version tracking
- Add CONTRIBUTING.md for contributor guidelines
- Fix helper files registration in composer.json
- Update all placeholders with actual information"

# Push to GitHub
git push origin main
```

## Step 3: Create a Git Tag

Packagist uses Git tags to determine package versions.

```bash
# Create an annotated tag for version 1.0.0
git tag -a v1.0.0 -m "Release version 1.0.0

Initial stable release of Pop Framework with:
- Vertical Slice Architecture implementation
- Zero external dependencies
- Custom Inertia.js adapter
- Multi-database support
- RBAC permission system
- Auto-discovery for routes, middleware, helpers"

# Push the tag to GitHub
git push origin v1.0.0
```

**Verify on GitHub:**
- Go to your repository on GitHub
- Click "Releases" (or "Tags")
- You should see `v1.0.0` listed

## Step 4: Register on Packagist

### 4.1 Create Packagist Account

1. Go to [https://packagist.org/](https://packagist.org/)
2. Click "Sign Up" or "Login with GitHub" (recommended)
3. If using GitHub login, authorize Packagist

### 4.2 Submit Your Package

1. Once logged in, click your username (top-right)
2. Click "Submit" from the dropdown menu
3. Enter your repository URL:
   ```
   https://github.com/YOUR_USERNAME/pop
   ```
4. Click "Check"
5. Packagist will validate your repository
6. If validation passes, click "Submit"

### 4.3 Validation Requirements

Packagist will check:
- ✅ Valid composer.json exists
- ✅ Package name is unique (pop-framework/pop)
- ✅ Repository is publicly accessible
- ✅ License is specified
- ✅ Valid version tag exists

**Common Issues:**

| Issue | Solution |
|-------|----------|
| "Package name already taken" | Choose a different package name |
| "No valid composer.json" | Ensure composer.json is in root directory |
| "Repository not accessible" | Make repository public on GitHub |
| "Invalid package name" | Use format: `vendor/package` |

## Step 5: Configure Auto-Update Webhook

To automatically update Packagist when you push to GitHub:

### 5.1 Get Your API Token

1. On Packagist, go to your profile
2. Click "Show API Token"
3. Copy the token (you'll need it for GitHub webhook)

### 5.2 Set Up GitHub Webhook

1. Go to your GitHub repository
2. Click "Settings" → "Webhooks" → "Add webhook"
3. Configure webhook:
   ```
   Payload URL: https://packagist.org/api/github?username=YOUR_PACKAGIST_USERNAME
   Content type: application/json
   Secret: [Your API token from Packagist]
   Which events: Just the push event
   Active: ✓ Checked
   ```
4. Click "Add webhook"

**Or use Packagist's automatic method:**

1. On your Packagist package page, look for "GitHub Service Hook"
2. Click "Enable" next to it
3. Packagist will automatically set up the webhook (requires GitHub OAuth)

## Step 6: Verify Installation

Test that your package can be installed:

```bash
# Create a new test project
composer create-project pop-framework/pop test-install

# Navigate into it
cd test-install

# Install dependencies
composer install
npm install

# Test the framework
php -S localhost:8000 -t Infrastructure/Http/Public
```

Visit `http://localhost:8000` - you should see your framework running!

## Step 7: Post-Publication Checklist

- [ ] Package appears on Packagist: `https://packagist.org/packages/pop-framework/pop`
- [ ] Installation works: `composer create-project pop-framework/pop test-app`
- [ ] Auto-update webhook is configured
- [ ] GitHub repository has description and topics
- [ ] README displays correctly on GitHub
- [ ] Create a GitHub Release for v1.0.0
- [ ] Announce on social media/forums (optional)

### Create GitHub Release

1. Go to your GitHub repository
2. Click "Releases" → "Create a new release"
3. Choose tag: `v1.0.0`
4. Release title: `Pop Framework v1.0.0`
5. Description: Copy from CHANGELOG.md
6. Attach any binary assets (if applicable)
7. Click "Publish release"

## Step 8: Monitor and Maintain

### View Package Statistics

- Packagist page: `https://packagist.org/packages/pop-framework/pop`
- Stats include: downloads, stars, dependents

### Update Package

When releasing new versions:

```bash
# Make your changes
git add .
git commit -m "feat: Add new feature"
git push origin main

# Create new version tag
git tag -a v1.1.0 -m "Release version 1.1.0"
git push origin v1.1.0
```

Packagist will automatically update (if webhook is configured).

### Versioning Guide

Follow [Semantic Versioning](https://semver.org/):

- **MAJOR** version (2.0.0) - Breaking changes
- **MINOR** version (1.1.0) - New features, backward compatible
- **PATCH** version (1.0.1) - Bug fixes, backward compatible

## Troubleshooting

### Package Not Showing on Packagist

1. Check repository is public
2. Verify composer.json is valid: `composer validate`
3. Ensure you pushed the tag: `git push origin v1.0.0`
4. Check Packagist error messages

### Installation Fails

```bash
# Clear Composer cache
composer clear-cache

# Try installing with verbose output
composer create-project pop-framework/pop test-app -vvv
```

### Webhook Not Working

1. Check webhook delivery on GitHub (Settings → Webhooks → Recent Deliveries)
2. Verify API token is correct
3. Manually update on Packagist: Click "Update" button on package page

## Additional Resources

- **Packagist Documentation**: [https://packagist.org/about](https://packagist.org/about)
- **Composer Documentation**: [https://getcomposer.org/doc/](https://getcomposer.org/doc/)
- **Semantic Versioning**: [https://semver.org/](https://semver.org/)
- **Keep a Changelog**: [https://keepachangelog.com/](https://keepachangelog.com/)

## Support

If you encounter issues:
- Check [Packagist Support](https://packagist.org/about#how-to-update-packages)
- Review [Composer issues](https://github.com/composer/composer/issues)
- Ask on PHP community forums

---

**Congratulations!** Once published, users worldwide can install your framework with:

```bash
composer create-project pop-framework/pop awesome-project
```

🎉 Your framework is now part of the global PHP ecosystem!
