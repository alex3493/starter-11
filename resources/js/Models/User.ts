import ModelBase from '@/Models/ModelBase.js'

export default class UserModel extends ModelBase {
    name: string
    email: string | undefined

    constructor(user: UserModel) {
        super(user)

        this.name = user.name || ''
        this.email = user.email || undefined
    }
}
