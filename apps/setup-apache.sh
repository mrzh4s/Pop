#!/bin/bash

echo "==================================="
echo "Pop Framework - Apache2 Setup"
echo "==================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "This script needs sudo privileges. Running with sudo..."
    sudo "$0" "$@"
    exit $?
fi

# Add pop.test to /etc/hosts if not exists
if ! grep -q "pop.test" /etc/hosts; then
    echo "Adding pop.test to /etc/hosts..."
    echo "127.0.0.1    pop.test" >> /etc/hosts
    echo "✓ Added pop.test to hosts file"
else
    echo "✓ pop.test already in hosts file"
fi

# Disable old site if exists
if [ -f /etc/apache2/sites-enabled/pop.test.conf ]; then
    echo "Disabling old configuration..."
    a2dissite pop.test.conf
fi

# Copy Apache config
echo ""
echo "Creating Apache virtual host..."
cp /home/user/Pop/apache2.conf.example /etc/apache2/sites-available/pop.test.conf
echo "✓ Created /etc/apache2/sites-available/pop.test.conf"

# Enable required Apache modules
echo ""
echo "Enabling Apache modules..."
a2enmod rewrite
a2enmod proxy
a2enmod proxy_http
a2enmod headers
a2enmod php8.4

# Enable the site
echo ""
echo "Enabling pop.test site..."
a2ensite pop.test.conf

# Test Apache configuration
echo ""
echo "Testing Apache configuration..."
if apache2ctl configtest; then
    echo "✓ Apache configuration is valid"

    # Restart Apache
    echo ""
    echo "Restarting Apache2..."
    systemctl restart apache2
    echo "✓ Apache2 restarted"

    echo ""
    echo "==================================="
    echo "Setup Complete!"
    echo "==================================="
    echo ""
    echo "Your site is now available at:"
    echo "  → http://pop.test"
    echo ""
    echo "Next steps:"
    echo "  1. Make sure Vite dev server is running: npm run dev"
    echo "  2. Visit http://pop.test/dashboard in your browser"
    echo ""
else
    echo "✗ Apache configuration test failed"
    echo "Please check the configuration and try again"
    exit 1
fi
