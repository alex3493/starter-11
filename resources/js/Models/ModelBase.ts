export default class ModelBase {
    id: string | number;

    created_at: string | undefined;

    constructor(model: ModelBase) {
        this.id = model.id || '';
        this.created_at = model.created_at || undefined;
    }

    getCreatedAt() {
        if(!this.created_at) return ''
        const date = new Date(this.created_at)
        return date.toLocaleString()
    }

    isValidId(): boolean {
        return !!this.id;
    }
}
