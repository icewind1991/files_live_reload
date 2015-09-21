import {LiveReload} from './LiveReload';

OC.Plugins.register('OCA.Files.FileList', {
	attach: (fileList) => {
		const reloader = new LiveReload(fileList);
		reloader.start();
	}
});
