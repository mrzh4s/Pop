# Pop Framework

**Minimal PHP Template - Ready for Your JS Framework**

A lightweight, production-ready PHP template that you can use as-is or easily integrate with React, Vue, Svelte, or any other JavaScript framework. Built with simplicity and flexibility in mind.

## ✨ Features

- 🚀 **Zero Configuration** - Works out of the box
- 🎯 **Minimal** - Only what you need, nothing more
- ⚡ **Fast** - Lightweight and performant
- 🔧 **Flexible** - Easy to extend and customize
- 🎨 **Modern** - Ready for modern JS frameworks
- 📦 **Production Ready** - Includes server configs

## 🚀 Quick Start

```bash
# Clone or download the template
git clone https://github.com/yourusername/pop-framework.git
cd pop-framework

# Run installation
./install.sh

# Start development server
php -S localhost:8000 -t infrastructure/http/public
```

Visit **http://localhost:8000** - You're ready to go!

## 📁 File Structure

```
Pop/
├── infrastructure/
│   ├── http/
│   │   └── public/
│   │       ├── index.php          ← Entry point
│   │       └── assets/            ← Built JS/CSS here
│   └── view/
│       └── welcome.php            ← Welcome page
├── src/                           ← Your PHP code
│   ├── Controllers/
│   ├── Models/
│   └── Services/
├── storage/                       ← Logs, cache, uploads
│   ├── logs/
│   ├── cache/
│   ├── sessions/
│   └── uploads/
├── config/                        ← Configuration files
├── composer.json                  ← PHP dependencies
├── package.json                   ← JS dependencies (optional)
├── .env.example                   ← Environment template
├── apache2.conf.example           ← Apache config
├── nginx.conf.example             ← Nginx config
└── install.sh                     ← Installation script
```

## 🎨 Adding a JavaScript Framework

Visit the welcome page at `http://localhost:8000` for detailed integration guides with **copy-paste ready examples**:

- ⚛️ **React** - Component-based UI library with complete single-page demo
- 💚 **Vue.js** - Progressive framework with complete single-page demo
- 🔥 **Svelte** - Compile-time framework with complete single-page demo

Each framework guide includes:
- ✅ Step-by-step setup instructions
- ✅ Vite configuration for API proxy
- ✅ Complete working example code
- ✅ Styling examples
- ✅ Copy buttons for easy use

### Quick Integration Example

```bash
# Install React with Vite
npm create vite@latest frontend -- --template react
cd frontend

# Configure Vite proxy (see welcome page for details)
# Run both servers:
# Terminal 1: php -S localhost:8000 -t infrastructure/http/public
# Terminal 2: cd frontend && npm run dev
```

## 🔧 Configuration

### Environment Variables

Copy `.env.example` to `.env` and customize:

```bash
cp .env.example .env
```

Edit `.env` for your environment settings.

### Adding Routes

Edit `infrastructure/http/public/index.php` to add your routes:

```php
// Example: Add a new API route
if ($uri === '/api/users' && $method === 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['users' => []]);
    exit;
}
```

### Adding Views

Create PHP files in `infrastructure/view/`:

```php
// infrastructure/view/about.php
<!DOCTYPE html>
<html>
<head>
    <title>About</title>
</head>
<body>
    <h1>About Page</h1>
</body>
</html>
```

Then route to it in `index.php`:

```php
if ($uri === '/about') {
    require ROOT_PATH . '/infrastructure/view/about.php';
    exit;
}
```

## 🚀 Production Deployment

### Apache Setup

Complete Apache configuration with security, caching, and SSL:

```bash
# Copy the config
sudo cp apache2.conf.example /etc/apache2/sites-available/pop.conf

# Update paths in the config file
sudo nano /etc/apache2/sites-available/pop.conf

# Enable required modules
sudo a2enmod rewrite headers expires deflate ssl

# Enable the site
sudo a2ensite pop

# Test and restart
sudo apache2ctl configtest
sudo systemctl restart apache2
```

See [apache2.conf.example](apache2.conf.example) for the complete configuration.

### Nginx Setup

Complete Nginx configuration with FastCGI, security, and SSL:

```bash
# Copy the config
sudo cp nginx.conf.example /etc/nginx/sites-available/pop

# Update paths and PHP version in the config
sudo nano /etc/nginx/sites-available/pop

# Create symbolic link
sudo ln -s /etc/nginx/sites-available/pop /etc/nginx/sites-enabled/

# Test and reload
sudo nginx -t
sudo systemctl reload nginx
```

See [nginx.conf.example](nginx.conf.example) for the complete configuration.

## 📚 Documentation

- **Quick Start**: Run `./install.sh` and visit `http://localhost:8000`
- **Framework Integration**: See the welcome page for React/Vue/Svelte guides
- **Server Setup**: Check [apache2.conf.example](apache2.conf.example) or [nginx.conf.example](nginx.conf.example)

## 🔒 Security

The template includes:
- ✅ Environment variable support
- ✅ Secure directory structure (public entry point)
- ✅ Security headers (in server configs)
- ✅ CORS configuration for development
- ✅ Upload directory protection

## 🤝 Contributing

This is a template project. Feel free to fork and customize for your needs!

## 📝 License

MIT License - see [LICENSE](LICENSE) for details.

---

**Ready to build?** Just run `./install.sh` and visit http://localhost:8000!

Need help? Check the beautiful welcome page with step-by-step guides for React, Vue, and Svelte integration!
