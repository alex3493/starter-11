import ModelBase from '@/Models/ModelBase.js'
import UserModel from "@/Models/User";
import ChatModel from "@/Models/ChatModel";

export default class ChatMessageModel extends ModelBase {
    user: UserModel

    message: string

    chat: ChatModel

    can: any // TODO: should we introduce ChatPermissions model?

    constructor(chatMessage: ChatMessageModel) {
        super(chatMessage)

        this.user = new UserModel(chatMessage.user)

        this.message = chatMessage.message

        this.chat = new ChatModel(chatMessage.chat)

        this.can = chatMessage.can || {
            delete: undefined,
            update: undefined,
        }
    }

    getCreatedAt() {
        if(!this.created_at) return ''
        const date = new Date(this.created_at)
        return date.toLocaleString()
    }
}
