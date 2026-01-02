<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pop Framework - Minimal PHP Template</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        border: "hsl(214.3 31.8% 91.4%)",
                        input: "hsl(214.3 31.8% 91.4%)",
                        ring: "hsl(222.2 84% 4.9%)",
                        background: "hsl(0 0% 100%)",
                        foreground: "hsl(222.2 84% 4.9%)",
                        primary: {
                            DEFAULT: "hsl(222.2 47.4% 11.2%)",
                            foreground: "hsl(210 40% 98%)",
                        },
                        secondary: {
                            DEFAULT: "hsl(210 40% 96.1%)",
                            foreground: "hsl(222.2 47.4% 11.2%)",
                        },
                        destructive: {
                            DEFAULT: "hsl(0 84.2% 60.2%)",
                            foreground: "hsl(210 40% 98%)",
                        },
                        muted: {
                            DEFAULT: "hsl(210 40% 96.1%)",
                            foreground: "hsl(215.4 16.3% 46.9%)",
                        },
                        accent: {
                            DEFAULT: "hsl(210 40% 96.1%)",
                            foreground: "hsl(222.2 47.4% 11.2%)",
                        },
                        card: {
                            DEFAULT: "hsl(0 0% 100%)",
                            foreground: "hsl(222.2 84% 4.9%)",
                        },
                    },
                    borderRadius: {
                        lg: "0.5rem",
                        md: "calc(0.5rem - 2px)",
                        sm: "calc(0.5rem - 4px)",
                    },
                    keyframes: {
                        "fade-in": {
                            "0%": { opacity: "0", transform: "translateY(10px)" },
                            "100%": { opacity: "1", transform: "translateY(0)" },
                        },
                    },
                    animation: {
                        "fade-in": "fade-in 0.5s ease-out",
                    },
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fade-in 0.3s ease-out;
        }

        pre {
            overflow-x: auto;
        }

        code {
            font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-white to-slate-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <header class="mb-8 text-center animate-fade-in">
            <div class="inline-flex items-center justify-center p-2 bg-primary/5 rounded-full mb-4">
                <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-2xl">
                    🚀
                </div>
            </div>
            <h1 class="text-5xl font-bold text-foreground mb-3 tracking-tight">Pop Framework</h1>
            <p class="text-xl text-muted-foreground mb-6">Minimal PHP Template - Ready for Your JS Framework</p>

            <!-- Status Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 border border-emerald-200 rounded-full text-sm font-medium text-emerald-700">
                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                Server Running · PHP <?php echo PHP_VERSION; ?>
                <span class="mx-2 text-emerald-300">·</span>
                <a href="/api/hello" class="hover:underline font-semibold" target="_blank">Test API</a>
            </div>
        </header>

        <!-- Tabs -->
        <div class="mb-6 border-b border-border">
            <nav class="flex gap-1 -mb-px overflow-x-auto" role="tablist">
                <button onclick="showTab('overview')"
                        class="tab-button px-6 py-3 text-sm font-medium transition-colors border-b-2 border-transparent hover:border-muted-foreground/30 data-[active=true]:border-primary data-[active=true]:text-primary whitespace-nowrap"
                        data-active="true">
                    <span class="inline-flex items-center gap-2">
                        <span>📋</span>
                        <span>Overview</span>
                    </span>
                </button>
                <button onclick="showTab('react')"
                        class="tab-button px-6 py-3 text-sm font-medium transition-colors border-b-2 border-transparent hover:border-muted-foreground/30 data-[active=true]:border-primary data-[active=true]:text-primary whitespace-nowrap">
                    <span class="inline-flex items-center gap-2">
                        <span>⚛️</span>
                        <span>React</span>
                    </span>
                </button>
                <button onclick="showTab('vue')"
                        class="tab-button px-6 py-3 text-sm font-medium transition-colors border-b-2 border-transparent hover:border-muted-foreground/30 data-[active=true]:border-primary data-[active=true]:text-primary whitespace-nowrap">
                    <span class="inline-flex items-center gap-2">
                        <span>💚</span>
                        <span>Vue</span>
                    </span>
                </button>
                <button onclick="showTab('svelte')"
                        class="tab-button px-6 py-3 text-sm font-medium transition-colors border-b-2 border-transparent hover:border-muted-foreground/30 data-[active=true]:border-primary data-[active=true]:text-primary whitespace-nowrap">
                    <span class="inline-flex items-center gap-2">
                        <span>🔥</span>
                        <span>Svelte</span>
                    </span>
                </button>
            </nav>
        </div>

        <!-- Overview Tab -->
        <div id="overview" class="tab-content active space-y-6">
            <!-- Hero Card -->
            <div class="bg-card border border-border rounded-lg p-8 shadow-sm">
                <h2 class="text-2xl font-semibold mb-3">What is Pop Framework?</h2>
                <p class="text-muted-foreground leading-relaxed">
                    A <strong class="text-foreground">minimal PHP template</strong> that gives you a clean foundation to build modern web applications.
                    Use it as a pure PHP backend or integrate it with React, Vue, or Svelte for a full-stack solution.
                </p>
            </div>

            <!-- Features Grid -->
            <div class="grid md:grid-cols-2 gap-4">
                <div class="bg-card border border-border rounded-lg p-6 hover:shadow-md transition-shadow">
                    <h3 class="font-semibold mb-3 flex items-center gap-2">
                        <span class="text-lg">✨</span>
                        Features
                    </h3>
                    <ul class="space-y-2 text-sm text-muted-foreground">
                        <li class="flex items-start gap-2">
                            <span class="text-primary mt-0.5">→</span>
                            <span>Simple routing system</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary mt-0.5">→</span>
                            <span>Environment configuration</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary mt-0.5">→</span>
                            <span>Clean architecture</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary mt-0.5">→</span>
                            <span>Easy JS framework integration</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary mt-0.5">→</span>
                            <span>Production ready</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-card border border-border rounded-lg p-6 hover:shadow-md transition-shadow">
                    <h3 class="font-semibold mb-3 flex items-center gap-2">
                        <span class="text-lg">📁</span>
                        File Structure
                    </h3>
                    <ul class="space-y-2 text-sm text-muted-foreground font-mono">
                        <li class="flex items-start gap-2">
                            <span class="text-primary">→</span>
                            <span>infrastructure/http/public/index.php</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary">→</span>
                            <span>infrastructure/view/</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary">→</span>
                            <span>src/</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-primary">→</span>
                            <span>config/</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Quick Start -->
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
                <h3 class="font-semibold mb-3 flex items-center gap-2 text-amber-900">
                    <span class="text-lg">🚀</span>
                    Quick Start
                </h3>
                <div class="relative">
                    <pre class="bg-slate-900 text-slate-50 p-4 rounded-md overflow-x-auto text-sm"><code># Start PHP development server
php -S localhost:8000 -t infrastructure/http/public

# Visit http://localhost:8000</code></pre>
                    <button onclick="copyCode(this)"
                            class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors font-medium">
                        Copy
                    </button>
                </div>
            </div>

            <!-- Choose Framework -->
            <div class="bg-card border border-border rounded-lg p-8">
                <h2 class="text-2xl font-semibold mb-3">Choose Your Path</h2>
                <p class="text-muted-foreground mb-4">
                    Click on the tabs above to see complete integration guides for:
                </p>
                <div class="grid md:grid-cols-3 gap-4">
                    <div class="p-4 bg-secondary rounded-lg">
                        <div class="text-3xl mb-2">⚛️</div>
                        <h4 class="font-semibold mb-1">React</h4>
                        <p class="text-sm text-muted-foreground">Component-based UI with massive ecosystem</p>
                    </div>
                    <div class="p-4 bg-secondary rounded-lg">
                        <div class="text-3xl mb-2">💚</div>
                        <h4 class="font-semibold mb-1">Vue.js</h4>
                        <p class="text-sm text-muted-foreground">Progressive framework with intuitive API</p>
                    </div>
                    <div class="p-4 bg-secondary rounded-lg">
                        <div class="text-3xl mb-2">🔥</div>
                        <h4 class="font-semibold mb-1">Svelte</h4>
                        <p class="text-sm text-muted-foreground">Compile-time framework with minimal runtime</p>
                    </div>
                </div>
                <p class="text-sm text-muted-foreground mt-4">
                    Each tab includes a complete single-page example you can copy and run immediately!
                </p>
            </div>
        </div>

        <!-- React Tab -->
        <div id="react" class="tab-content space-y-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="text-5xl">⚛️</div>
                <div>
                    <h2 class="text-3xl font-bold text-[#61dafb] mb-2">React Integration</h2>
                    <p class="text-muted-foreground">Build modern UIs with component-based architecture</p>
                </div>
            </div>

            <!-- Step 1 -->
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="bg-muted px-6 py-4 border-b border-border">
                    <h3 class="font-semibold">Step 1: Install React with Vite</h3>
                </div>
                <div class="p-6">
                    <div class="relative">
                        <pre class="bg-slate-900 text-emerald-400 p-4 rounded-md overflow-x-auto text-sm"><code>npm create vite@latest frontend -- --template react
cd frontend
npm install</code></pre>
                        <button onclick="copyCode(this)"
                                class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="bg-muted px-6 py-4 border-b border-border">
                    <h3 class="font-semibold">Step 2: Configure Vite for API Proxy</h3>
                    <p class="text-sm text-muted-foreground mt-1">Update <code class="px-1.5 py-0.5 bg-secondary rounded text-xs">frontend/vite.config.js</code></p>
                </div>
                <div class="p-6">
                    <div class="relative">
                        <pre class="bg-slate-900 text-slate-50 p-4 rounded-md overflow-x-auto text-sm"><code>import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': 'http://localhost:8000'
    }
  }
})</code></pre>
                        <button onclick="copyCode(this)"
                                class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="bg-muted px-6 py-4 border-b border-border">
                    <h3 class="font-semibold">Step 3: Create Your First Component</h3>
                    <p class="text-sm text-muted-foreground mt-1">Replace <code class="px-1.5 py-0.5 bg-secondary rounded text-xs">frontend/src/App.jsx</code></p>
                </div>
                <div class="p-6">
                    <div class="relative">
                        <pre class="bg-slate-900 text-slate-50 p-4 rounded-md overflow-x-auto text-sm"><code>import { useState, useEffect } from 'react'
import './App.css'

function App() {
  const [message, setMessage] = useState('')
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    fetch('/api/hello')
      .then(res => res.json())
      .then(data => {
        setMessage(data.message)
        setLoading(false)
      })
  }, [])

  return (
    &lt;div className="app"&gt;
      &lt;h1&gt;🚀 React + Pop Framework&lt;/h1&gt;
      {loading ? (
        &lt;p&gt;Loading...&lt;/p&gt;
      ) : (
        &lt;&gt;
          &lt;p className="message"&gt;{message}&lt;/p&gt;
          &lt;p className="info"&gt;Connected to PHP backend!&lt;/p&gt;
        &lt;/&gt;
      )}
    &lt;/div&gt;
  )
}

export default App</code></pre>
                        <button onclick="copyCode(this)"
                                class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Run Instructions -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-6">
                <h3 class="font-semibold mb-3 text-emerald-900">🎯 Run Both Servers</h3>
                <div class="relative mb-4">
                    <pre class="bg-slate-900 text-emerald-400 p-4 rounded-md overflow-x-auto text-sm"><code># Terminal 1: PHP Backend
php -S localhost:8000 -t infrastructure/http/public

# Terminal 2: React Frontend
cd frontend && npm run dev</code></pre>
                    <button onclick="copyCode(this)"
                            class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                        Copy
                    </button>
                </div>
                <p class="text-sm text-emerald-800">
                    Visit <strong class="font-mono">http://localhost:5173</strong> to see your React app!
                </p>
            </div>
        </div>

        <!-- Vue Tab -->
        <div id="vue" class="tab-content space-y-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="text-5xl">💚</div>
                <div>
                    <h2 class="text-3xl font-bold text-[#42b983] mb-2">Vue.js Integration</h2>
                    <p class="text-muted-foreground">Progressive framework with intuitive API</p>
                </div>
            </div>

            <!-- Step 1 -->
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="bg-muted px-6 py-4 border-b border-border">
                    <h3 class="font-semibold">Step 1: Install Vue with Vite</h3>
                </div>
                <div class="p-6">
                    <div class="relative">
                        <pre class="bg-slate-900 text-emerald-400 p-4 rounded-md overflow-x-auto text-sm"><code>npm create vite@latest frontend -- --template vue
cd frontend
npm install</code></pre>
                        <button onclick="copyCode(this)"
                                class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="bg-muted px-6 py-4 border-b border-border">
                    <h3 class="font-semibold">Step 2: Configure Vite for API Proxy</h3>
                    <p class="text-sm text-muted-foreground mt-1">Update <code class="px-1.5 py-0.5 bg-secondary rounded text-xs">frontend/vite.config.js</code></p>
                </div>
                <div class="p-6">
                    <div class="relative">
                        <pre class="bg-slate-900 text-slate-50 p-4 rounded-md overflow-x-auto text-sm"><code>import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': 'http://localhost:8000'
    }
  }
})</code></pre>
                        <button onclick="copyCode(this)"
                                class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="bg-muted px-6 py-4 border-b border-border">
                    <h3 class="font-semibold">Step 3: Create Your First Component</h3>
                    <p class="text-sm text-muted-foreground mt-1">Replace <code class="px-1.5 py-0.5 bg-secondary rounded text-xs">frontend/src/App.vue</code></p>
                </div>
                <div class="p-6">
                    <div class="relative">
                        <pre class="bg-slate-900 text-slate-50 p-4 rounded-md overflow-x-auto text-sm"><code>&lt;template&gt;
  &lt;div class="app"&gt;
    &lt;h1&gt;🚀 Vue + Pop Framework&lt;/h1&gt;
    &lt;p v-if="loading"&gt;Loading...&lt;/p&gt;
    &lt;template v-else&gt;
      &lt;p class="message"&gt;{{ message }}&lt;/p&gt;
      &lt;p class="info"&gt;Connected to PHP backend!&lt;/p&gt;
    &lt;/template&gt;
  &lt;/div&gt;
&lt;/template&gt;

&lt;script setup&gt;
import { ref, onMounted } from 'vue'

const message = ref('')
const loading = ref(true)

onMounted(async () => {
  const response = await fetch('/api/hello')
  const data = await response.json()
  message.value = data.message
  loading.value = false
})
&lt;/script&gt;

&lt;style scoped&gt;
.app {
  max-width: 800px;
  margin: 0 auto;
  padding: 40px 20px;
  text-align: center;
}

h1 {
  color: #42b983;
  font-size: 3em;
  margin-bottom: 30px;
}

.message {
  font-size: 1.5em;
  color: #333;
  padding: 20px;
  background: #f0f0f0;
  border-radius: 8px;
  margin: 20px 0;
}

.info {
  color: #28a745;
  font-weight: bold;
}
&lt;/style&gt;</code></pre>
                        <button onclick="copyCode(this)"
                                class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Run Instructions -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-6">
                <h3 class="font-semibold mb-3 text-emerald-900">🎯 Run Both Servers</h3>
                <div class="relative mb-4">
                    <pre class="bg-slate-900 text-emerald-400 p-4 rounded-md overflow-x-auto text-sm"><code># Terminal 1: PHP Backend
php -S localhost:8000 -t infrastructure/http/public

# Terminal 2: Vue Frontend
cd frontend && npm run dev</code></pre>
                    <button onclick="copyCode(this)"
                            class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                        Copy
                    </button>
                </div>
                <p class="text-sm text-emerald-800">
                    Visit <strong class="font-mono">http://localhost:5173</strong> to see your Vue app!
                </p>
            </div>
        </div>

        <!-- Svelte Tab -->
        <div id="svelte" class="tab-content space-y-6">
            <div class="flex items-start gap-4 mb-6">
                <div class="text-5xl">🔥</div>
                <div>
                    <h2 class="text-3xl font-bold text-[#ff3e00] mb-2">Svelte Integration</h2>
                    <p class="text-muted-foreground">Compile-time framework with minimal runtime</p>
                </div>
            </div>

            <!-- Step 1 -->
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="bg-muted px-6 py-4 border-b border-border">
                    <h3 class="font-semibold">Step 1: Install Svelte with Vite</h3>
                </div>
                <div class="p-6">
                    <div class="relative">
                        <pre class="bg-slate-900 text-emerald-400 p-4 rounded-md overflow-x-auto text-sm"><code>npm create vite@latest frontend -- --template svelte
cd frontend
npm install</code></pre>
                        <button onclick="copyCode(this)"
                                class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="bg-muted px-6 py-4 border-b border-border">
                    <h3 class="font-semibold">Step 2: Configure Vite for API Proxy</h3>
                    <p class="text-sm text-muted-foreground mt-1">Update <code class="px-1.5 py-0.5 bg-secondary rounded text-xs">frontend/vite.config.js</code></p>
                </div>
                <div class="p-6">
                    <div class="relative">
                        <pre class="bg-slate-900 text-slate-50 p-4 rounded-md overflow-x-auto text-sm"><code>import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'

export default defineConfig({
  plugins: [svelte()],
  server: {
    proxy: {
      '/api': 'http://localhost:8000'
    }
  }
})</code></pre>
                        <button onclick="copyCode(this)"
                                class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="bg-card border border-border rounded-lg overflow-hidden">
                <div class="bg-muted px-6 py-4 border-b border-border">
                    <h3 class="font-semibold">Step 3: Create Your First Component</h3>
                    <p class="text-sm text-muted-foreground mt-1">Replace <code class="px-1.5 py-0.5 bg-secondary rounded text-xs">frontend/src/App.svelte</code></p>
                </div>
                <div class="p-6">
                    <div class="relative">
                        <pre class="bg-slate-900 text-slate-50 p-4 rounded-md overflow-x-auto text-sm"><code>&lt;script&gt;
  import { onMount } from 'svelte';

  let message = '';
  let loading = true;

  onMount(async () => {
    const response = await fetch('/api/hello');
    const data = await response.json();
    message = data.message;
    loading = false;
  });
&lt;/script&gt;

&lt;div class="app"&gt;
  &lt;h1&gt;🚀 Svelte + Pop Framework&lt;/h1&gt;
  {#if loading}
    &lt;p&gt;Loading...&lt;/p&gt;
  {:else}
    &lt;p class="message"&gt;{message}&lt;/p&gt;
    &lt;p class="info"&gt;Connected to PHP backend!&lt;/p&gt;
  {/if}
&lt;/div&gt;

&lt;style&gt;
  .app {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px 20px;
    text-align: center;
  }

  h1 {
    color: #ff3e00;
    font-size: 3em;
    margin-bottom: 30px;
  }

  .message {
    font-size: 1.5em;
    color: #333;
    padding: 20px;
    background: #f0f0f0;
    border-radius: 8px;
    margin: 20px 0;
  }

  .info {
    color: #28a745;
    font-weight: bold;
  }
&lt;/style&gt;</code></pre>
                        <button onclick="copyCode(this)"
                                class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                            Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- Run Instructions -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-6">
                <h3 class="font-semibold mb-3 text-emerald-900">🎯 Run Both Servers</h3>
                <div class="relative mb-4">
                    <pre class="bg-slate-900 text-emerald-400 p-4 rounded-md overflow-x-auto text-sm"><code># Terminal 1: PHP Backend
php -S localhost:8000 -t infrastructure/http/public

# Terminal 2: Svelte Frontend
cd frontend && npm run dev</code></pre>
                    <button onclick="copyCode(this)"
                            class="absolute top-2 right-2 px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded-md transition-colors">
                        Copy
                    </button>
                </div>
                <p class="text-sm text-emerald-800">
                    Visit <strong class="font-mono">http://localhost:5173</strong> to see your Svelte app!
                </p>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-12 pt-8 border-t border-border text-center text-sm text-muted-foreground">
            <p class="mb-2"><strong class="text-foreground">Pop Framework</strong> · Minimal & Flexible</p>
            <p>Edit <code class="px-1.5 py-0.5 bg-secondary rounded text-xs font-mono">infrastructure/view/welcome.php</code> to customize this page</p>
        </footer>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));

            // Remove active from all buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(btn => btn.setAttribute('data-active', 'false'));

            // Show selected tab
            document.getElementById(tabName).classList.add('active');

            // Mark button as active
            event.target.closest('.tab-button').setAttribute('data-active', 'true');
        }

        function copyCode(button) {
            const codeBlock = button.parentElement.querySelector('code');
            const text = codeBlock.textContent;

            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.classList.add('bg-emerald-600');
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('bg-emerald-600');
                }, 2000);
            });
        }
    </script>
</body>
</html>
