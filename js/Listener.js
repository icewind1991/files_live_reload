export class Listener {
	constructor (updater, eventSource) {
		this.updater = updater;
		this.eventSource = eventSource;
	}

	listen () {
		this.eventSource.addEventListener('rename', ({data}) => {
			const parsed = JSON.parse(data);
			const {source, target} = parsed;
			this.updater.rename(source, target, parsed);
		});

		this.eventSource.addEventListener('write', ({data}) => {
			this.updater.write(JSON.parse(data));
		});

		this.eventSource.addEventListener('delete', ({data}) => {
			const {path} = JSON.parse(data);
			this.updater.remove(path);
		});
	}
}
