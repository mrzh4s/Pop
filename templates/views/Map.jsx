import React from 'react';
import { Head } from '@inertiajs/react';

export default function Map() {
  return (
    <>
      <Head title="Map" />

      <div className="min-h-screen bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-900">Map View</h1>
            <p className="mt-2 text-gray-600">Interactive map visualization</p>
          </div>

          <div className="bg-white rounded-lg shadow">
            <div className="aspect-video flex items-center justify-center bg-gray-100 rounded-lg">
              <div className="text-center">
                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                <p className="mt-2 text-sm text-gray-500">Map component will be loaded here</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
