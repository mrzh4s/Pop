const { default: axios } = require("axios");

module.exports = {
	output: 'apps/assets',
	entry: {
		fontawesome: [
			{
				src: ['node_modules/@fortawesome/fontawesome-pro/css/all.min.css'],
				dist: '/vendors/fontawesome/css/fontawesome.min.css',
			},
			{
				src: ['node_modules/@fortawesome/fontawesome-pro/webfonts/'],
				dist: '/vendors/fontawesome/webfonts/',
			},
		],
		'@form-validation': [
			{
				src: ['src/vendors/@form-validation/umd/styles'],
				dist: '/vendors/@form-validation',
			},
			{
				src: [
					'src/vendors/@form-validation/umd/bundle/popular.min.js',
					'src/vendors/@form-validation/umd/bundle/full.min.js',
					'src/vendors/@form-validation/umd/plugin-bootstrap5/index.min.js',
				],
				dist: '/vendors/@form-validation/form-validation.bundle.js',
				bundle: true,
			},
		],
		apexcharts: [
			{
				src: ['node_modules/apexcharts/dist/apexcharts.css'],
				dist: '/vendors/apexcharts/apexcharts.css',
			},
			{
				src: ['node_modules/apexcharts/dist/apexcharts.min.js'],
				dist: '/vendors/apexcharts/apexcharts.min.js',
			},
		],
		prismjs: [
			{
				src: [
					'node_modules/prismjs/prism.js',
					'node_modules/prismjs/components/prism-markup.js',
					'node_modules/prismjs/components/prism-markup-templating.js',
					'node_modules/prismjs/components/prism-bash.js',
					'node_modules/prismjs/components/prism-javascript.js',
					'node_modules/prismjs/components/prism-css.js',
					'node_modules/prismjs/plugins/normalize-whitespace/prism-normalize-whitespace.js',
					'src/vendors/prismjs/prismjs.init.js',
				],
				dist: '/vendors/prismjs/prismjs.min.js',
				bundle: true,
			},
		],
		clipboard: [
			{
				src: ['node_modules/clipboard/dist/clipboard.min.js'],
				dist: '/vendors/clipboard/clipboard.min.js',
			},
		],
		ktui: [
			{
				src: ['node_modules/@keenthemes/ktui/dist/ktui.min.js'],
				dist: '/vendors/ktui/ktui.min.js',
			},
		],
		fullcalendar: [
			{
				src: [
					'node_modules/fullcalendar/index.global.min.js',
				],
				dist: '/vendors/fullcalendar/fullcalendar.min.js',
			}
		],
		mapboxgl: [
			{
				src: [
					'node_modules/mapbox-gl/dist/mapbox-gl.css',
					'node_modules/@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.css',
					'node_modules/@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.css',
				],
				dist: '/vendors/mapboxgl/mapboxgl.bundle.css',
				bundle: true
			},
			{
				src: [
					'node_modules/mapbox-gl/dist/mapbox-gl.js',
					'node_modules/@mapbox/mapbox-gl-geocoder/dist/mapbox-gl-geocoder.min.js',
					'node_modules/@mapbox/mapbox-gl-draw/dist/mapbox-gl-draw.js',
					'node_modules/@turf/turf/turf.min.js',
				],
				dist: '/vendors/mapboxgl/mapboxgl.bundle.js',
				bundle: true
			}
		],
		fslightbox: [
			{
				src: ['node_modules/fslightbox/index.js'],
				dist: '/vendors/fslightbox/fslightbox.min.js',
			}
		],
		quill: [
			{
				src: ['node_modules/quill/dist/quill.snow.css'],
				dist: '/vendors/quill/quill.min.css',
				bundle: true
			},
			{
				src: ['node_modules/quill/dist/quill.js'],
				dist: '/vendors/quill/quill.min.js',
				bundle: true
			}
		],
		moment: [
			{
				src: ['node_modules/moment/moment.js'],
				dist: '/vendors/moment/moment.min.js',
				bundle: true
			}
		],
		axios: [
			{
				src: ['node_modules/axios/dist/axios.min.js'],
				dist: '/vendors/axios/axios.min.js',
				bundle: true
			}
		]
	},
};
