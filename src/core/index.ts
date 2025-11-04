/*
 * Metronic
 * @author: Keenthemes
 * Copyright 2024 Keenthemes
 */

import KTDom from './helpers/dom';
import KTUtils from './helpers/utils';
import KTEventHandler from './helpers/event-handler';
import { KTMenu } from './components/menu';
import { ApiHelper, api }from './components/api/index.js';

export { KTMenu } from './components/menu';

const KTComponents = {
	/**
	 * Initializes all KT components.
	 * This method is called on initial page load and after Livewire navigation.
	 */
	init(): void {
		try {
			KTMenu.init();
		} catch (error) {
			console.warn('KTMenu initialization failed:', error);
		}
	},
};

declare global {
	interface Window {
		KTUtils: typeof KTUtils;
		KTDom: typeof KTDom;
		KTEventHandler: typeof KTEventHandler;
		KTMenu: typeof KTMenu;
		KTComponents: typeof KTComponents;
		api: typeof api;
		helper: typeof ApiHelper;
	}
}

window.KTUtils = KTUtils;
window.KTDom = KTDom;
window.KTEventHandler = KTEventHandler;
window.KTMenu = KTMenu;
window.KTComponents = KTComponents;
window.api = api;
window.helper = ApiHelper;

export default KTComponents;

KTDom.ready(() => {
	KTComponents.init();
});
