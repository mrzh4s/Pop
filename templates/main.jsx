import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import './styles/app.css';

// Import all page components
const views = import.meta.glob('./views/**/*.jsx', { eager: true });

// Get the app element
const el = document.getElementById('app');

// Parse initial page data
const initialPage = JSON.parse(el.dataset.page);

createInertiaApp({
  id: 'app',
  resolve: (name) => {
    const page = views[`./views/${name}.jsx`];
    if (!page) {
      throw new Error(`Page not found: ${name}`);
    }
    return page.default;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
  progress: {
    color: '#4B5563',
    showSpinner: true,
  },
  page: initialPage,
});
