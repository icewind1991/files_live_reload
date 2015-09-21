export class ListUpdater {
	modelCache = {};

	constructor (fileList) {
		this.fileList = fileList;
	}

	isInCurrentFolder (path) {
		const dir = (this.fileList._currentDirectory === '/') ? '' : this.fileList._currentDirectory;
		return OC.dirname(path) === dir;
	}

	getModelForFile (path) {
		if (!this.isInCurrentFolder(path)) {
			return null;
		}
		const name = OC.basename(path);
		if (!this.modelCache[path]) {
			this.modelCache[path] = this.fileList.getModelForFile(name);
		}
		return this.modelCache[path];
	}

	rename (from, to, data) {
		if (this.isInCurrentFolder(from) && this.isInCurrentFolder(to)) {
			const model = this.getModelForFile(from);
			if (model) {
				model.set('name', OC.basename(to));
			}
		} else if (this.isInCurrentFolder(from)) {
			this.remove(from);
		} else if (this.isInCurrentFolder(to)) {
			console.log(data);
			data.path = to;
			this.write(data);
		}
	}

	write (data) {
		if (!this.isInCurrentFolder(data.path)) {
			return null;
		}
		console.log('put', data);
		const model = this.getModelForFile(data.path);
		if (model) {
			for (let key in data) {
				model.set(key, data[key]);
			}
		} else {
			this.fileList.add(data);
		}
	}

	remove (path) {
		if (!this.isInCurrentFolder(path)) {
			return null;
		}
		this.fileList.remove(OC.basename(path));
	}
}
