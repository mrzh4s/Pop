# React + Vite Architecture in Pop Framework

## Overview

Pop Framework uses a **modern hybrid architecture** that combines:
- **Backend**: PHP (routing, controllers, business logic)
- **Frontend**: React (UI components, interactivity)
- **Build Tool**: Vite (development server, hot module replacement, production builds)
- **Bridge**: Inertia.js (connects PHP and React without building an API)

## Why It Works Perfectly

### 1. **Inertia.js Bridge** 🌉

Inertia acts as a **glue layer** between PHP and React, eliminating the need for a separate API.

**Traditional SPA Architecture:**
```
Browser ↔ React App ↔ REST API ↔ PHP Backend
         (fetch)     (JSON)
```

**Pop Framework Architecture:**
```
Browser ↔ React Components ↔ Inertia ↔ PHP Controllers
         (No API needed!)
```

#### How Inertia Works:

**Initial Page Load:**
```
1. Browser requests: GET /dashboard
2. Apache routes to: apps/index.php
3. Router calls: DashboardPage@show()
4. Controller returns: Inertia::render('Dashboard', $props)
5. Inertia renders: apps/templates/app.php
6. HTML sent with: <div id="app" data-page='{"component":"Dashboard",...}'/>
7. React reads data-page and hydrates the Dashboard component
```

**Subsequent Navigation (SPA mode):**
```
1. User clicks link: <Link href="/map">
2. Inertia intercepts and sends: XHR GET /map (with X-Inertia: true header)
3. PHP returns: JSON {"component":"Map","props":{...}}
4. React swaps to Map component WITHOUT full page reload
```

### 2. **Vite Development Server** ⚡

Vite provides **instant Hot Module Replacement (HMR)** during development.

#### Development Mode Flow:

```
┌─────────────────────────────────────────────────────────────┐
│                    Browser: http://pop.test                  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                 Apache (Port 80/443)                         │
│  DocumentRoot: /home/user/Pop/apps                          │
└─────────────────────────────────────────────────────────────┘
                              │
                 ┌────────────┴────────────┐
                 │                         │
                 ▼                         ▼
┌──────────────────────────┐  ┌──────────────────────────────┐
│   PHP Request            │  │   Asset Request              │
│   /dashboard             │  │   /@vite/client              │
│   /map                   │  │   /src/main.jsx              │
│                          │  │                              │
│   ↓                      │  │   ↓ (Proxied to Vite)       │
│   apps/index.php         │  │                              │
│   ↓                      │  │   ┌────────────────────────┐ │
│   Router → Controller    │  │   │  Vite Dev Server       │ │
│   ↓                      │  │   │  (Port 5173)           │ │
│   Inertia::render()      │  │   │                        │ │
│   ↓                      │  │   │  - Transforms JSX      │ │
│   apps/templates/app.php │  │   │  - Hot reload          │ │
│                          │  │   │  - Fast refresh        │ │
│   HTML:                  │  │   └────────────────────────┘ │
│   <script src="http://   │  │            ↓                 │
│    localhost:5173/       │  │   ES Modules (unbundled)     │
│    @vite/client">        │  │                              │
│   <script src="http://   │  └──────────────────────────────┘
│    localhost:5173/       │
│    src/main.jsx">        │
└──────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────┐
│                    React App Renders                         │
│  - Reads data-page attribute                                 │
│  - Hydrates React component based on "component" name        │
│  - Props passed from PHP are available in React              │
└─────────────────────────────────────────────────────────────┘
```

#### Why This Is Fast:

1. **No bundling in dev**: Vite serves ES modules directly
2. **HMR**: Changes reflect instantly without full reload
3. **On-demand compilation**: Only compiles files you're viewing
4. **Pre-bundled dependencies**: node_modules are cached

### 3. **Apache Proxy Configuration** 🔄

Apache proxies Vite requests so everything works through a single domain.

**From [apache2.conf.example](../apache2.conf.example:20-23):**
```apache
# Proxy Vite dev server for development (HMR)
RewriteCond %{REQUEST_URI} ^/@vite/ [OR]
RewriteCond %{REQUEST_URI} ^/src/
RewriteRule ^(.*)$ http://localhost:5173/$1 [P,L]
```

This means:
- `http://pop.test/@vite/client` → proxied to `http://localhost:5173/@vite/client`
- `http://pop.test/src/main.jsx` → proxied to `http://localhost:5173/src/main.jsx`

**Benefits:**
- ✓ No CORS issues
- ✓ Single domain for everything
- ✓ WebSocket connections work (for HMR)

### 4. **Production Build Flow** 📦

In production, Vite pre-builds everything into optimized static assets.

```
npm run build
     ↓
┌────────────────────────────────────────┐
│         Vite Build Process             │
│                                        │
│  1. Read: src/main.jsx                │
│  2. Transform: JSX → JS               │
│  3. Bundle: Import all components     │
│  4. Minify: Remove whitespace         │
│  5. Hash: Add content hash to names   │
│  6. Output: apps/assets/              │
│                                        │
│     js/main-a1b2c3d4.js              │
│     css/main-e5f6g7h8.css            │
└────────────────────────────────────────┘
         ↓
┌────────────────────────────────────────┐
│    apps/templates/app.php detects     │
│    APP_ENV !== 'local'                │
│                                        │
│    Loads pre-built assets:            │
│    <link href="/assets/css/...css">   │
│    <script src="/assets/js/...js">    │
└────────────────────────────────────────┘
```

### 5. **Template Intelligence** 🧠

The PHP template [apps/templates/app.php](../apps/templates/app.php:11-19) switches between dev and production:

```php
<?php if (env('APP_ENV') === 'local'): ?>
    <!-- Development: Vite Dev Server -->
    <script type="module" src="http://localhost:5173/@vite/client"></script>
    <script type="module" src="http://localhost:5173/src/main.jsx"></script>
<?php else: ?>
    <!-- Production: Built Assets -->
    <link rel="stylesheet" href="/apps/assets/css/main.css">
    <script type="module" src="/apps/assets/js/main.js"></script>
<?php endif; ?>
```

**Development** (`APP_ENV=local`):
- Loads from Vite dev server
- Hot reload enabled
- Fast refresh on save

**Production** (`APP_ENV=production`):
- Loads pre-built assets
- Minified and optimized
- Cache-friendly with content hashes

## Complete Request Flow

### Example: Loading Dashboard Page

#### Step 1: Initial Request
```
User → http://pop.test/dashboard
```

#### Step 2: Apache Routing
```
Apache receives request
  ↓ (No file exists at /dashboard)
  ↓ (RewriteRule activates)
  ↓
Routes to: /apps/index.php
```

#### Step 3: PHP Bootstrap
```
apps/index.php
  ↓ define('ROOT_PATH', ...)
  ↓ require bootstrap.php
  ↓ require routes.php
  ↓
Router matches: /dashboard → DashboardPage@show()
```

#### Step 4: Controller Execution
```php
// apps/pages/DashboardPage.php
public function show() {
    return Inertia::render('Dashboard', [
        'user' => ['name' => 'John'],
        'stats' => ['total' => 100]
    ]);
}
```

#### Step 5: Inertia Processing
```php
// apps/core/Inertia.php
Inertia::render('Dashboard', $props)
  ↓ Check: Is this XHR? (No - initial load)
  ↓ Create page object:
    {
      component: 'Dashboard',
      props: { user: ..., stats: ... },
      url: '/dashboard',
      version: '1.0'
    }
  ↓ Encode to JSON
  ↓ Render template with JSON in data-page attribute
```

#### Step 6: HTML Response
```html
<!DOCTYPE html>
<html>
<head>
    <script type="module" src="http://localhost:5173/@vite/client"></script>
    <script type="module" src="http://localhost:5173/src/main.jsx"></script>
</head>
<body>
    <div id="app" data-page='{"component":"Dashboard","props":{...}}'></div>
</body>
</html>
```

#### Step 7: Browser Loads Assets
```
Browser requests:
  → http://localhost:5173/@vite/client
  → http://localhost:5173/src/main.jsx
     ↓ (Apache proxies to Vite)
     ↓
  Vite Dev Server responds with:
  → Transformed ES modules
  → React libraries
  → All imported components
```

#### Step 8: React Initialization
```javascript
// src/main.jsx
const el = document.getElementById('app');
const initialPage = JSON.parse(el.dataset.page);

createInertiaApp({
  page: initialPage,  // { component: 'Dashboard', props: {...} }
  resolve: (name) => {
    // Loads src/pages/Dashboard.jsx
    return pages[`./pages/${name}.jsx`].default;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  }
});
```

#### Step 9: Component Renders
```jsx
// src/pages/Dashboard.jsx
export default function Dashboard({ user, stats }) {
  return (
    <div>
      <h1>Welcome {user.name}!</h1>
      <p>Total: {stats.total}</p>
    </div>
  );
}
```

## Why This Architecture Is Powerful

### 1. **No API Needed** 🚫
- Direct PHP → React data passing
- No JSON serialization overhead
- No API versioning complexity
- Type-safe props from PHP to React

### 2. **Best of Both Worlds** ⚖️
- **PHP**: Server-side logic, database access, authentication
- **React**: Rich interactivity, component reusability, modern UI

### 3. **Developer Experience** 🎨
- **Hot reload**: Changes appear instantly
- **Fast builds**: Vite is 10-100x faster than Webpack
- **Simple debugging**: Clear error messages in both PHP and React

### 4. **Production Performance** 🚀
- **Code splitting**: Only load what's needed
- **Tree shaking**: Remove unused code
- **Minification**: Smaller bundle sizes
- **Caching**: Content-hashed filenames

### 5. **Flexible Deployment** 📦
- **Single codebase**: One git repo for everything
- **Single server**: No separate API server needed
- **Easy hosting**: Standard PHP hosting works
- **Progressive enhancement**: Works without JavaScript (if needed)

## File Structure

```
/home/user/Pop/
├── apps/                           # PHP Application
│   ├── index.php                  # Entry point
│   ├── core/
│   │   ├── bootstrap.php          # Framework initialization
│   │   ├── Router.php             # Request routing
│   │   └── Inertia.php            # PHP ↔ React bridge
│   ├── pages/
│   │   └── DashboardPage.php      # PHP controller
│   ├── routes/
│   │   └── web.php                # Route definitions
│   ├── templates/
│   │   └── app.php                # HTML template (dev/prod switch)
│   └── config/
│       ├── Database.php           # Dynamic DB config
│       └── Ftp.php                # Dynamic FTP config
│
├── src/                            # React Application
│   ├── main.jsx                   # React entry point
│   ├── pages/
│   │   ├── Dashboard.jsx          # React component
│   │   └── Map.jsx                # React component
│   └── styles/
│       └── app.css                # Tailwind imports
│
├── vite.config.js                 # Vite configuration
├── package.json                   # NPM dependencies
└── apache2.conf.example           # Apache + Vite proxy
```

## Development Workflow

### Starting Development

```bash
# Terminal 1: Start Vite dev server
npm run dev

# Terminal 2: Apache is already running (system service)
# Just visit: http://pop.test/dashboard
```

### Making Changes

**PHP Changes:**
```php
// Edit: apps/pages/DashboardPage.php
public function show() {
    return Inertia::render('Dashboard', [
        'newProp' => 'value'  // ← Add new prop
    ]);
}
```
↓ Refresh browser to see changes

**React Changes:**
```jsx
// Edit: src/pages/Dashboard.jsx
export default function Dashboard({ newProp }) {
    return <div>{newProp}</div>  // ← Use new prop
}
```
↓ **Instant hot reload** (no refresh needed!)

### Building for Production

```bash
npm run build
```

Output:
```
apps/assets/
├── js/
│   └── main-a1b2c3d4.js      # Minified, tree-shaken
├── css/
│   └── main-e5f6g7h8.css     # Minified Tailwind
└── manifest.json              # Asset mapping
```

Change `.env`:
```bash
APP_ENV=production
```

Template automatically switches to production assets!

## Key Technologies

| Technology | Purpose | Why It Works |
|------------|---------|--------------|
| **PHP 8.4** | Backend framework | Fast, mature, easy deployment |
| **React 19** | UI library | Component-based, huge ecosystem |
| **Vite 7** | Build tool | Lightning fast, modern ESM |
| **Inertia.js** | PHP ↔ React bridge | No API needed, type-safe |
| **Apache 2.4** | Web server | Reliable, proxy support |
| **Tailwind 4** | CSS framework | Utility-first, zero runtime |

## Common Patterns

### Passing Data to React

**PHP Controller:**
```php
return Inertia::render('Dashboard', [
    'user' => User::find($id),
    'posts' => Post::latest()->limit(10)->get(),
    'settings' => config('app')
]);
```

**React Component:**
```jsx
export default function Dashboard({ user, posts, settings }) {
    return (
        <div>
            <h1>{user.name}</h1>
            {posts.map(post => <Post key={post.id} {...post} />)}
        </div>
    );
}
```

### Navigation

**PHP:**
```php
// Redirect
return redirect('/dashboard');

// Named route
return redirect()->route('dashboard');
```

**React:**
```jsx
import { Link } from '@inertiajs/react';

<Link href="/dashboard">Go to Dashboard</Link>
```

### Forms

**React:**
```jsx
import { useForm } from '@inertiajs/react';

function CreatePost() {
    const { data, setData, post } = useForm({
        title: '',
        body: ''
    });

    function submit(e) {
        e.preventDefault();
        post('/posts');  // ← Sends to PHP
    }

    return (
        <form onSubmit={submit}>
            <input value={data.title}
                   onChange={e => setData('title', e.target.value)} />
            <button type="submit">Create</button>
        </form>
    );
}
```

**PHP:**
```php
// apps/routes/web.php
$router->post('/posts', 'PostPage@store');

// apps/pages/PostPage.php
public function store() {
    $title = $_POST['title'];
    $body = $_POST['body'];

    Post::create(['title' => $title, 'body' => $body]);

    return redirect('/posts');
}
```

## Summary

React + Vite works perfectly in Pop Framework because:

1. ✅ **Inertia.js** eliminates API complexity
2. ✅ **Vite** provides instant feedback during development
3. ✅ **Apache proxy** makes everything work on one domain
4. ✅ **Smart templates** switch between dev/prod automatically
5. ✅ **Dynamic configs** make it flexible and scalable
6. ✅ **Single codebase** keeps everything organized
7. ✅ **Modern tooling** leverages the best of 2025 tech

This is a **production-ready, enterprise-grade architecture** used by modern frameworks like Laravel Breeze, Rails with Inertia, and others!
