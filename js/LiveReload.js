import {ListUpdater} from './ListUpdater';
import {Listener} from './Listener';

export class LiveReload {
	supportsEventSource () {
		return typeof EventSource !== 'undefined';
	}

	constructor (fileList) {
		this.updater = new ListUpdater(fileList);
	}

	start () {
		if (!this.supportsEventSource()) {
			return;
		}
		this.eventSource = new EventSource(OC.generateUrl('/apps/files_live_reload/listen'));
		this.listener = new Listener(this.updater, this.eventSource);
		this.listener.listen();
	}
}
