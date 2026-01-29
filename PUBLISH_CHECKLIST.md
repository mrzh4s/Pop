# 🚀 Packagist Publication Checklist

## ✅ Completed Items

### Package Configuration
- [x] composer.json properly configured
  - [x] Package name: `mrzh4s/pop-framework`
  - [x] Type: `project` (correct for starter kit)
  - [x] Author: mrzh4s (mrzh4s@gmail.com)
  - [x] Homepage: https://github.com/mrzh4s/Pop
  - [x] Support URLs configured
  - [x] Helper files in autoload.files (15 files)
  - [x] PSR-4 autoloading configured
  - [x] Keywords added
  - [x] License: MIT
  - [x] Composer validation: PASSED ✓

### Documentation
- [x] README.md created with:
  - [x] Package overview
  - [x] Installation: `composer create-project mrzh4s/pop-framework my-app`
  - [x] Feature highlights
  - [x] Quick start guide
  - [x] Architecture explanation
  - [x] Code examples
  - [x] Support links

- [x] CHANGELOG.md created
  - [x] Follows "Keep a Changelog" format
  - [x] Version 1.0.0 documented
  - [x] All features listed
  - [x] GitHub URLs updated

- [x] CONTRIBUTING.md created
  - [x] Development setup instructions
  - [x] Coding standards (PSR-12)
  - [x] PR process documented
  - [x] Testing requirements
  - [x] GitHub URLs updated

- [x] LICENSE file
  - [x] MIT License
  - [x] Copyright: 2026 Seri Langkasuka

- [x] Packagist guide created (docs/PACKAGIST_GUIDE.md)

### Repository
- [x] .gitignore configured
  - [x] node_modules, vendor excluded
  - [x] .env excluded, .env.example included
  - [x] Build output excluded
  - [x] CLAUDE.md excluded (internal docs)
  - [x] .npmrc excluded

### Validation
- [x] No placeholder text remaining (except in contributor instructions)
- [x] composer.json syntax valid
- [x] All essential files present

---

## 📝 Next Steps (To Publish)

### 1. Final Review (5 minutes)

Review these files one more time:
```bash
cat composer.json  # Verify all info is correct
cat README.md      # Check installation command works
cat LICENSE        # Verify copyright
```

### 2. Commit Changes (2 minutes)

```bash
# Check what's changed
git status

# Add all files
git add .

# Commit
git commit -m "chore: Prepare for Packagist publication

- Update composer.json with package metadata
- Add README.md, CHANGELOG.md, CONTRIBUTING.md
- Fix helper files registration in autoload
- Update all URLs to github.com/mrzh4s/Pop
- Update copyright to Seri Langkasuka"

# Push to GitHub
git push origin main
```

### 3. Create Version Tag (1 minute)

**Option A: Stable Release (if you have tests)**
```bash
git tag -a v1.0.0 -m "Release version 1.0.0

Initial stable release of Pop Framework:
- Vertical Slice Architecture implementation
- Zero external dependencies
- Custom Inertia.js adapter
- Multi-database support (SQLite, PostgreSQL, MySQL, SQL Server)
- RBAC permission system with role hierarchy
- Auto-discovery for routes, middleware, helpers
- Blade template engine
- CSRF protection and session management"

git push origin v1.0.0
```

**Option B: Beta Release (recommended - no tests yet)**
```bash
git tag -a v0.9.0 -m "Beta release 0.9.0

Pre-release version for testing and feedback.
All features complete, test coverage pending."

git push origin v0.9.0
```

### 4. Publish to Packagist (5 minutes)

1. Go to **https://packagist.org/**
2. Click **"Login with GitHub"** (top-right)
3. Authorize Packagist
4. Click your username → **"Submit"**
5. Enter repository URL: `https://github.com/mrzh4s/Pop`
6. Click **"Check"** → Wait for validation
7. Click **"Submit"** → Package published! 🎉

### 5. Set Up Auto-Update (2 minutes)

On your Packagist package page:
1. Look for **"GitHub Service Hook"** section
2. Click **"Enable"**
3. Packagist will auto-configure webhook

Or manually:
- Copy API token from Packagist profile
- Add webhook to GitHub repo settings
- Payload URL: `https://packagist.org/api/github?username=mrzh4s`

### 6. Verify Installation (3 minutes)

Test that your package installs correctly:

```bash
# In a different directory
cd /tmp
composer create-project mrzh4s/pop-framework test-install
cd test-install
composer install
npm install
php -S localhost:8000 -t Infrastructure/Http/Public
```

Visit http://localhost:8000 - should see your framework! ✓

---

## 📊 Publication Status

**Ready to publish:** YES ✅

**Recommended version:** v0.9.0 (beta)
- Why beta? Framework has 0% test coverage
- Add tests later → release v1.0.0 stable

**Package will be available at:**
- Packagist: `https://packagist.org/packages/mrzh4s/pop-framework`
- Install: `composer create-project mrzh4s/pop-framework my-app`

---

## ⚠️ Important Notes

### Before Publishing

1. **GitHub Repository Must Be Public**
   - Packagist can't access private repos
   - Go to Settings → Danger Zone → Change visibility

2. **Ensure Clean Git State**
   ```bash
   git status  # Should show "nothing to commit"
   ```

3. **Tag Must Exist**
   ```bash
   git tag  # Should list your version tag
   ```

### After Publishing

1. **Add GitHub Repository Details**
   - Description: "Modern PHP framework with Vertical Slice Architecture"
   - Website: Link to Packagist
   - Topics: `php`, `framework`, `vertical-slice`, `inertia`, `react`, `vsa`

2. **Create GitHub Release**
   - Go to Releases → Create new release
   - Choose tag: v0.9.0 or v1.0.0
   - Copy release notes from CHANGELOG.md
   - Publish release

3. **Update README Badges (Optional)**
   ```markdown
   [![Latest Version](https://img.shields.io/packagist/v/mrzh4s/pop-framework.svg)](https://packagist.org/packages/mrzh4s/pop-framework)
   [![Total Downloads](https://img.shields.io/packagist/dt/mrzh4s/pop-framework.svg)](https://packagist.org/packages/mrzh4s/pop-framework)
   [![License](https://img.shields.io/packagist/l/mrzh4s/pop-framework.svg)](https://github.com/mrzh4s/Pop/blob/main/LICENSE)
   ```

---

## 🎯 Quick Publish Command

Copy-paste this to publish in one go:

```bash
# Review files
echo "=== Reviewing composer.json ===" && cat composer.json | head -20

# Commit
git add . && git commit -m "chore: Prepare for Packagist publication"

# Push
git push origin main

# Tag (choose beta or stable)
git tag -a v0.9.0 -m "Beta release 0.9.0" && git push origin v0.9.0

echo "✅ Ready for Packagist! Go to: https://packagist.org/packages/submit"
echo "Repository URL: https://github.com/mrzh4s/Pop"
```

---

## 🐛 Troubleshooting

### "Package name already taken"
- Choose different name in composer.json
- Try: `mrzh4s/pop`, `mrzh4s/pop-php`, etc.

### "No valid composer.json found"
- Run: `composer validate`
- Ensure file is in repository root

### "Repository not accessible"
- Make repository public on GitHub
- Check repository URL is correct

### Installation fails
```bash
composer clear-cache
composer create-project mrzh4s/pop-framework test -vvv
```

---

## 📚 Resources

- **Packagist Guide**: [docs/PACKAGIST_GUIDE.md](docs/PACKAGIST_GUIDE.md)
- **Packagist Docs**: https://packagist.org/about
- **Composer Docs**: https://getcomposer.org/doc/
- **Semantic Versioning**: https://semver.org/

---

## ✨ You're Ready!

Everything is configured and ready for publication. Just follow the steps above! 🚀

**Total time to publish:** ~15-20 minutes

Good luck! 🎉
