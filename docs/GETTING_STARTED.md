# Getting Started with Pop Framework

Complete guide to start developing with React + Vite + PHP + Inertia.

## Quick Start (5 Minutes)

### 1. Setup Apache & Hosts

```bash
# Run the setup script
./setup-apache.sh

# Or manually:
sudo cp apache2.conf.example /etc/apache2/sites-available/pop.test.conf
sudo a2enmod rewrite proxy proxy_http headers
sudo a2ensite pop.test.conf
sudo sh -c 'echo "127.0.0.1    pop.test" >> /etc/hosts'
sudo systemctl restart apache2
```

### 2. Start Vite Dev Server

```bash
# Install dependencies (first time only)
npm install

# Start development server
npm run dev
```

You should see:
```
VITE v7.2.6  ready in XXX ms

➜  Local:   http://localhost:5173/
➜  Network: use --host to expose
➜  press h + enter to show help
```

### 3. Visit Your App

Open browser: **http://pop.test/dashboard**

You should see the Dashboard page with React components!

## Daily Development Workflow

### Start Your Day

```bash
# Terminal 1: Start Vite (keep this running)
npm run dev

# Terminal 2: Work with your code
code .
```

### Creating a New Page

#### Step 1: Create PHP Controller

**File:** `apps/pages/ProfilePage.php`
```php
<?php

require_once ROOT_PATH . '/pages/BasePage.php';

class ProfilePage extends BasePage {

    public function show() {
        // Get data from database, session, etc.
        $user = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => '/media/avatars/john.jpg'
        ];

        $stats = [
            'posts' => 42,
            'followers' => 1337,
            'following' => 256
        ];

        // Pass data to React component
        return Inertia::render('Profile', [
            'user' => $user,
            'stats' => $stats
        ]);
    }
}
```

#### Step 2: Create React Component

**File:** `src/pages/Profile.jsx`
```jsx
import { Head } from '@inertiajs/react';

export default function Profile({ user, stats }) {
  return (
    <>
      <Head title={`${user.name}'s Profile`} />

      <div className="min-h-screen bg-gray-50">
        <div className="max-w-4xl mx-auto px-4 py-8">
          {/* Profile Header */}
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center space-x-4">
              <img
                src={user.avatar}
                alt={user.name}
                className="w-20 h-20 rounded-full"
              />
              <div>
                <h1 className="text-2xl font-bold">{user.name}</h1>
                <p className="text-gray-600">{user.email}</p>
              </div>
            </div>
          </div>

          {/* Stats */}
          <div className="grid grid-cols-3 gap-4 mt-6">
            <div className="bg-white rounded-lg shadow p-4 text-center">
              <div className="text-3xl font-bold text-blue-600">
                {stats.posts}
              </div>
              <div className="text-gray-600">Posts</div>
            </div>
            <div className="bg-white rounded-lg shadow p-4 text-center">
              <div className="text-3xl font-bold text-green-600">
                {stats.followers}
              </div>
              <div className="text-gray-600">Followers</div>
            </div>
            <div className="bg-white rounded-lg shadow p-4 text-center">
              <div className="text-3xl font-bold text-purple-600">
                {stats.following}
              </div>
              <div className="text-gray-600">Following</div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
```

#### Step 3: Add Route

**File:** `apps/routes/web.php`
```php
// Add this line
$router->get('/profile', 'ProfilePage@show', ['auth'])->name('profile');
```

#### Step 4: Test It

Visit: **http://pop.test/profile**

Changes to `Profile.jsx` will **hot reload automatically!** ⚡

### Working with Database

#### Configure Database Connection

**File:** `apps/.env`
```bash
# Main database (auto-discovered as 'main' connection)
MAIN_DB_HOST=localhost
MAIN_DB_PORT=5432
MAIN_DB_DATABASE=myapp
MAIN_DB_USERNAME=postgres
MAIN_DB_PASSWORD=secret

# Optional: Add more databases
ANALYTICS_DB_DRIVER=mysql
ANALYTICS_DB_HOST=analytics.example.com
ANALYTICS_DB_DATABASE=analytics
ANALYTICS_DB_USERNAME=root
ANALYTICS_DB_PASSWORD=secret
```

#### Use in PHP Controller

```php
// apps/pages/ProfilePage.php
public function show() {
    // Get default connection
    $db = DB::connection();

    // Or specific connection
    $db = DB::connection('main');
    $analyticsDb = DB::connection('analytics');

    // Query data
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([1]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return Inertia::render('Profile', [
        'user' => $user
    ]);
}
```

### Working with FTP

#### Configure FTP Connection

**File:** `apps/.env`
```bash
# Default FTP (auto-discovered as 'default' connection)
FTP_HOST=ftp.example.com
FTP_USERNAME=user
FTP_PASSWORD=pass
FTP_PATH=/public_html

# Backup FTP (auto-discovered as 'backup' connection)
BACKUP_FTP_HOST=backup.example.com
BACKUP_FTP_USERNAME=backup_user
BACKUP_FTP_PASSWORD=secret
BACKUP_FTP_PATH=/backups
```

#### Use in PHP

```php
// Get FTP configuration
$ftpConfig = require ROOT_PATH . '/config/Ftp.php';

// Default connection
$defaultFtp = $ftpConfig['connections']['default'];

// Backup connection
$backupFtp = $ftpConfig['connections']['backup'];

// Connect
$conn = ftp_connect($defaultFtp['host'], $defaultFtp['port']);
ftp_login($conn, $defaultFtp['username'], $defaultFtp['password']);

// Upload file
ftp_put($conn, '/remote/file.txt', '/local/file.txt', FTP_BINARY);
```

### Navigation Between Pages

#### Using Inertia Link (Client-side, faster)

```jsx
import { Link } from '@inertiajs/react';

<Link href="/profile" className="text-blue-600 hover:underline">
  View Profile
</Link>
```

#### Using Regular Link (Full page reload)

```jsx
<a href="/profile" className="text-blue-600 hover:underline">
  View Profile
</a>
```

### Forms & Data Submission

#### React Form Component

```jsx
import { useForm } from '@inertiajs/react';

export default function CreatePost() {
  const { data, setData, post, processing, errors } = useForm({
    title: '',
    body: '',
  });

  function submit(e) {
    e.preventDefault();
    post('/posts');
  }

  return (
    <form onSubmit={submit}>
      <div className="mb-4">
        <label className="block text-sm font-medium mb-2">
          Title
        </label>
        <input
          type="text"
          value={data.title}
          onChange={e => setData('title', e.target.value)}
          className="w-full px-3 py-2 border rounded"
        />
        {errors.title && (
          <div className="text-red-600 text-sm mt-1">{errors.title}</div>
        )}
      </div>

      <div className="mb-4">
        <label className="block text-sm font-medium mb-2">
          Body
        </label>
        <textarea
          value={data.body}
          onChange={e => setData('body', e.target.value)}
          className="w-full px-3 py-2 border rounded"
          rows="5"
        />
        {errors.body && (
          <div className="text-red-600 text-sm mt-1">{errors.body}</div>
        )}
      </div>

      <button
        type="submit"
        disabled={processing}
        className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      >
        {processing ? 'Creating...' : 'Create Post'}
      </button>
    </form>
  );
}
```

#### PHP Handler

```php
// apps/routes/web.php
$router->post('/posts', 'PostPage@store', ['auth']);

// apps/pages/PostPage.php
public function store() {
    $title = $_POST['title'] ?? '';
    $body = $_POST['body'] ?? '';

    // Validate
    $errors = [];
    if (empty($title)) {
        $errors['title'] = 'Title is required';
    }
    if (empty($body)) {
        $errors['body'] = 'Body is required';
    }

    if (!empty($errors)) {
        return Inertia::render('CreatePost', [
            'errors' => $errors
        ]);
    }

    // Save to database
    $db = DB::connection();
    $stmt = $db->prepare("INSERT INTO posts (title, body) VALUES (?, ?)");
    $stmt->execute([$title, $body]);

    // Redirect
    return redirect('/posts');
}
```

## Common Tasks

### Installing NPM Packages

```bash
# Install React libraries
npm install react-query axios lodash

# Install dev dependencies
npm install -D @types/lodash
```

### Using Installed Packages

```jsx
// src/pages/Dashboard.jsx
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import _ from 'lodash';

export default function Dashboard() {
  const { data } = useQuery({
    queryKey: ['stats'],
    queryFn: () => axios.get('/api/stats').then(res => res.data)
  });

  return <div>{_.capitalize('hello world')}</div>;
}
```

### Adding Tailwind Classes

```jsx
<div className="flex items-center justify-between p-4 bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow">
  <h2 className="text-xl font-bold text-gray-900">Title</h2>
  <button className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
    Click Me
  </button>
</div>
```

### Debugging

#### PHP Debugging

```php
// Log to error log
error_log('Debug: ' . print_r($data, true));

// Display in browser (only in dev)
if (env('APP_DEBUG')) {
    var_dump($data);
    die();
}
```

#### React Debugging

```jsx
// Console log
console.log('User data:', user);

// React DevTools (install browser extension)
// Inspect components in browser DevTools
```

### View Logs

```bash
# Apache error log
tail -f /var/log/apache2/pop-error.log

# Apache access log
tail -f /var/log/apache2/pop-access.log

# Vite output
# Already visible in terminal where you ran "npm run dev"
```

## Building for Production

### Step 1: Build Assets

```bash
npm run build
```

Output:
```
vite v7.2.6 building for production...
✓ 127 modules transformed.
apps/assets/css/main-e5f6g7h8.css   204.32 kB │ gzip: 24.15 kB
apps/assets/js/main-a1b2c3d4.js   2,156.84 kB │ gzip: 689.23 kB
✓ built in 3.45s
```

### Step 2: Update Environment

**File:** `apps/.env`
```bash
# Change from local to production
APP_ENV=production
APP_DEBUG=false
```

### Step 3: Clear Cache (if using)

```bash
# Clear any PHP opcode cache
sudo systemctl restart apache2
```

### Step 4: Test

Visit: **http://pop.test/dashboard**

The page should now load pre-built assets from `apps/assets/`

## Project Structure

```
/home/user/Pop/
├── apps/                          # PHP Backend
│   ├── index.php                 # Entry point
│   ├── .env                      # Environment config
│   ├── core/                     # Framework core
│   │   ├── bootstrap.php
│   │   ├── Router.php
│   │   ├── Inertia.php
│   │   └── Connection.php
│   ├── config/                   # Configuration
│   │   ├── Database.php         # Dynamic DB discovery
│   │   └── Ftp.php              # Dynamic FTP discovery
│   ├── pages/                    # PHP Controllers
│   │   ├── DashboardPage.php
│   │   └── ProfilePage.php
│   ├── routes/                   # Routes
│   │   ├── web.php
│   │   └── api.php
│   ├── templates/                # HTML templates
│   │   └── app.php
│   └── assets/                   # Built assets (production)
│       ├── js/
│       └── css/
│
├── src/                          # React Frontend
│   ├── main.jsx                 # React entry
│   ├── pages/                   # React components
│   │   ├── Dashboard.jsx
│   │   └── Profile.jsx
│   └── styles/
│       └── app.css
│
├── docs/                         # Documentation
│   ├── GETTING_STARTED.md       # This file
│   ├── DYNAMIC_CONFIGURATION.md
│   └── REACT_VITE_ARCHITECTURE.md
│
├── node_modules/                # NPM dependencies
├── package.json                 # NPM config
├── vite.config.js              # Vite config
├── apache2.conf.example        # Apache config
└── setup-apache.sh             # Setup script
```

## Troubleshooting

### Apache Not Starting

```bash
# Check configuration
sudo apache2ctl configtest

# Check error logs
sudo tail -f /var/log/apache2/error.log
```

### Vite Not Starting

```bash
# Check if port 5173 is in use
lsof -i :5173

# Kill process if needed
kill -9 <PID>

# Try again
npm run dev
```

### React Not Loading

1. Check Vite is running: `npm run dev`
2. Check Apache proxy is working: Visit `http://pop.test/@vite/client`
3. Check browser console for errors (F12)
4. Check `APP_ENV=local` in `.env`

### Database Connection Failed

1. Check credentials in `.env`
2. Test connection:
   ```bash
   psql -h localhost -U postgres -d v2
   ```
3. Check database is running:
   ```bash
   sudo systemctl status postgresql
   ```

### Hot Reload Not Working

1. Check Vite is running
2. Check browser console for WebSocket errors
3. Restart Vite: `Ctrl+C` then `npm run dev`
4. Clear browser cache

## Next Steps

- Read [DYNAMIC_CONFIGURATION.md](DYNAMIC_CONFIGURATION.md) for database/FTP setup
- Read [REACT_VITE_ARCHITECTURE.md](REACT_VITE_ARCHITECTURE.md) for architecture details
- Check [apps/.env.example](../apps/.env.example) for all configuration options
- Explore existing pages: [DashboardPage.php](../apps/pages/DashboardPage.php)
- Explore React components: [Dashboard.jsx](../src/pages/Dashboard.jsx)

## Quick Reference

### Common Commands

```bash
# Start development
npm run dev

# Build for production
npm run build

# Install package
npm install <package>

# Restart Apache
sudo systemctl restart apache2

# View logs
tail -f /var/log/apache2/pop-error.log
```

### Common Patterns

**Redirect in PHP:**
```php
return redirect('/dashboard');
```

**Link in React:**
```jsx
<Link href="/dashboard">Dashboard</Link>
```

**Get database:**
```php
$db = DB::connection('main');
```

**Render page:**
```php
return Inertia::render('ComponentName', ['prop' => $value]);
```

## Support

- GitHub Issues: [Report bugs or request features]
- Documentation: Check `docs/` folder
- Examples: Look at existing pages in `apps/pages/` and `src/pages/`

Happy coding! 🚀
