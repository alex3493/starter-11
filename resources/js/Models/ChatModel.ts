import ModelBase from '@/Models/ModelBase.js'
import UserModel from "@/Models/User";

export default class ChatModel extends ModelBase {
    topic: string

    users: UserModel[]

    can: any // TODO: should we introduce ChatPermissions model?

    constructor(chat: ChatModel) {
        super(chat)

        this.topic = chat.topic || ''

        this.users = chat.users.map(u => new UserModel(u)) || []

        this.can = chat.can || {
            delete: undefined,
            join: undefined,
            leave: undefined,
            update: undefined,
            listMessages: undefined,
        }
    }
}
