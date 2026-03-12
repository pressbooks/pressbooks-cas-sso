import { createWpViteConfig } from 'pressbooks-build-tools';
import { resolve } from 'path';

export default createWpViteConfig({
	input: {
		'login-form': resolve(__dirname, 'assets/src/scripts/login-form.js'),
	},
	outDir: 'assets/dist',
});
