# SSL/HTTPS Setup Guide

Complete guide to enable HTTPS for Pop Framework with automatic HTTP → HTTPS redirect.

## Quick Setup (Automated)

### One-Command Setup

```bash
./setup-apache.sh
```

This will:
1. ✓ Add `pop.test` to `/etc/hosts`
2. ✓ Generate self-signed SSL certificate
3. ✓ Configure Apache for HTTPS
4. ✓ Enable SSL module
5. ✓ Set up HTTP → HTTPS redirect
6. ✓ Restart Apache

## Manual Setup

If you prefer to set it up manually:

### Step 1: Generate SSL Certificate

```bash
./generate-ssl-cert.sh
```

This creates:
- Certificate: `/etc/ssl/certs/pop.test.crt`
- Private Key: `/etc/ssl/private/pop.test.key`

### Step 2: Update Apache Configuration

```bash
sudo cp apache2.conf.example /etc/apache2/sites-available/pop.test.conf
```

### Step 3: Enable SSL Module

```bash
sudo a2enmod ssl
sudo a2enmod rewrite
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod headers
```

### Step 4: Enable Site & Restart Apache

```bash
sudo a2ensite pop.test.conf
sudo apache2ctl configtest
sudo systemctl restart apache2
```

### Step 5: Update .env File

```bash
# apps/.env
APP_URL=https://pop.test
```

## How It Works

### HTTP to HTTPS Redirect

**Port 80 (HTTP):**
```apache
<VirtualHost *:80>
    ServerName pop.test

    # Redirect all HTTP to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>
```

When you visit `http://pop.test`, Apache automatically redirects to `https://pop.test`.

**Port 443 (HTTPS):**
```apache
<VirtualHost *:443>
    ServerName pop.test

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/pop.test.crt
    SSLCertificateKeyFile /etc/ssl/private/pop.test.key

    # Your application configuration...
</VirtualHost>
```

### Certificate Types

#### 1. Self-Signed Certificate (Development)

**Pros:**
- ✓ Free
- ✓ Works immediately
- ✓ Perfect for local development

**Cons:**
- ✗ Browser security warning
- ✗ Not trusted by default
- ✗ Only for development

**When to use:** Local development on `pop.test`

#### 2. Let's Encrypt Certificate (Production)

**Pros:**
- ✓ Free
- ✓ Trusted by all browsers
- ✓ Auto-renewal
- ✓ Production-ready

**Cons:**
- ✗ Requires public domain
- ✗ Requires port 80 open
- ✗ Not for local `.test` domains

**When to use:** Production deployment on real domain (e.g., `yoursite.com`)

## Browser Trust (Development Only)

### Chrome/Brave

1. Visit `https://pop.test`
2. Click "Advanced"
3. Click "Proceed to pop.test (unsafe)"
4. ✓ Site loads

**Permanent Trust (Chrome):**
```bash
# Export certificate
sudo openssl x509 -in /etc/ssl/certs/pop.test.crt -out ~/pop.test.crt

# Chrome Settings:
# 1. Settings → Privacy and security → Security → Manage certificates
# 2. Authorities tab → Import
# 3. Select ~/pop.test.crt
# 4. Check "Trust this certificate for identifying websites"
# 5. OK
```

### Firefox

1. Visit `https://pop.test`
2. Click "Advanced"
3. Click "Accept the Risk and Continue"
4. ✓ Site loads

**Permanent Trust (Firefox):**
1. Settings → Privacy & Security → View Certificates
2. Authorities → Import
3. Select `/etc/ssl/certs/pop.test.crt`
4. Check "Trust this CA to identify websites"
5. OK

### Safari (macOS)

1. Open Keychain Access
2. File → Import Items
3. Select `/etc/ssl/certs/pop.test.crt`
4. Double-click the certificate
5. Expand "Trust" section
6. Set "When using this certificate" to "Always Trust"
7. Close and enter password

## Production Setup (Let's Encrypt)

For production deployment on a real domain:

### Step 1: Install Certbot

```bash
sudo apt update
sudo apt install certbot python3-certbot-apache
```

### Step 2: Get Certificate

```bash
# Replace yoursite.com with your actual domain
sudo certbot --apache -d yoursite.com -d www.yoursite.com
```

### Step 3: Auto-Renewal

Certbot automatically sets up renewal. Test it:

```bash
sudo certbot renew --dry-run
```

### Step 4: Update Apache Config

Certbot updates Apache config automatically, but verify:

```apache
<VirtualHost *:443>
    ServerName yoursite.com

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yoursite.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yoursite.com/privkey.pem

    # Rest of your config...
</VirtualHost>
```

## Security Headers

The HTTPS configuration includes modern security headers:

```apache
# Prevent clickjacking
Header always set X-Frame-Options "SAMEORIGIN"

# Prevent MIME type sniffing
Header always set X-Content-Type-Options "nosniff"

# XSS protection
Header always set X-XSS-Protection "1; mode=block"

# Referrer policy
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

## SSL Configuration

Modern, secure SSL settings:

```apache
# Only use TLS 1.2 and 1.3
SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1

# Strong cipher suites
SSLCipherSuite HIGH:!aNULL:!MD5

# Server cipher preference
SSLHonorCipherOrder on
```

## Testing HTTPS

### Check Certificate

```bash
# View certificate details
openssl x509 -in /etc/ssl/certs/pop.test.crt -text -noout

# Check expiration date
openssl x509 -in /etc/ssl/certs/pop.test.crt -noout -dates
```

### Test SSL Configuration

```bash
# Test from command line
curl -v https://pop.test

# Ignore self-signed certificate warning
curl -k https://pop.test
```

### Online SSL Test (Production Only)

For production sites, test SSL configuration:
- https://www.ssllabs.com/ssltest/
- https://www.ssllabs.com/ssltest/analyze.html?d=yoursite.com

## Troubleshooting

### Certificate Not Found

```bash
# Check if certificate exists
ls -la /etc/ssl/certs/pop.test.crt
ls -la /etc/ssl/private/pop.test.key

# Regenerate if missing
./generate-ssl-cert.sh
```

### Apache Won't Start

```bash
# Check configuration
sudo apache2ctl configtest

# Check error log
sudo tail -f /var/log/apache2/error.log

# Common issue: SSL module not enabled
sudo a2enmod ssl
sudo systemctl restart apache2
```

### Mixed Content Warnings

If you see mixed content warnings in browser console:

1. Check all assets use HTTPS:
   ```jsx
   // Bad
   <img src="http://example.com/image.jpg" />

   // Good
   <img src="https://example.com/image.jpg" />
   ```

2. Check Vite proxy:
   ```apache
   # apache2.conf.example line 47-49
   # Proxies Vite dev server correctly
   ```

3. Update .env:
   ```bash
   APP_URL=https://pop.test
   ```

### Redirect Loop

If you get infinite redirects:

1. Check Apache config has both VirtualHosts (port 80 and 443)
2. Verify mod_rewrite is enabled:
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

3. Check .htaccess isn't interfering

### Certificate Expired

Self-signed certificates are valid for 365 days. Regenerate:

```bash
./generate-ssl-cert.sh
sudo systemctl restart apache2
```

## File Locations

| File | Purpose |
|------|---------|
| `/etc/ssl/certs/pop.test.crt` | SSL certificate |
| `/etc/ssl/private/pop.test.key` | Private key |
| `/etc/apache2/sites-available/pop.test.conf` | Apache config |
| `/home/user/Pop/apache2.conf.example` | Config template |
| `/home/user/Pop/generate-ssl-cert.sh` | Certificate generator |

## Quick Commands

```bash
# Check if HTTPS is working
curl -I https://pop.test

# Check SSL certificate
openssl s_client -connect pop.test:443 -servername pop.test

# View Apache SSL logs
sudo tail -f /var/log/apache2/pop-ssl-error.log
sudo tail -f /var/log/apache2/pop-ssl-access.log

# Restart Apache
sudo systemctl restart apache2

# Test Apache config
sudo apache2ctl configtest
```

## Summary

Your site now:
- ✅ Serves HTTPS on port 443
- ✅ Redirects HTTP → HTTPS (port 80 → 443)
- ✅ Uses modern SSL protocols (TLS 1.2+)
- ✅ Has security headers enabled
- ✅ Works with Vite dev server
- ✅ Ready for production (with Let's Encrypt)

Visit: **https://pop.test/dashboard** 🔒
