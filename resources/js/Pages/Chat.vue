<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {Head, router, useForm} from '@inertiajs/vue3'

import ChatModel from '@/Models/ChatModel'
import ChatMessageModel from "@/Models/ChatMessageModel";
import NavLink from "@/Components/NavLink.vue";
import Pagination from "@/Components/Pagination.vue";
import {computed, ref, watch} from "vue";
import ChatMessageRow from "@/Pages/Chat/ChatMessageRow.vue";
import TextInput from "@/Components/TextInput.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import CreateOrUpdateMessage from "@/Pages/Chat/CreateOrUpdateMessage.vue";
import {onBeforeUnmount, onMounted} from "vue";

const props = defineProps<{
    auth: any,
    chat: ChatModel,
    messages: {
        data: ChatMessageModel[],
        links: any[]
    },
    filters: {
        search: string | null
    },
    returnUrl?: string
}>()

const searchQuery = ref<string>(props.filters.search || '')

const showCreateModal = ref<boolean>(false)

const messageBeingEdited = ref<ChatMessageModel | undefined>(undefined)

const currentUserId = props.auth.user.id

const editMessage = (message: ChatMessageModel) => {
    messageBeingEdited.value = message
    showCreateModal.value = true
}

const deleteForm = useForm({
    id: ''
})

const deleteMessage = (message: ChatMessageModel) => {
    console.log('***** deleteMessage', message)

    if (!deleteForm.processing) {
        console.log('***** Deleting!')
        deleteForm.delete(`/chat/${message.chat.id}/message/${message.id}`, {
            preserveScroll: true
        })
    }
}

const onMessageModalClose = () => {
    messageBeingEdited.value = undefined
    showCreateModal.value = false
}

const refreshData = (search: string | undefined = undefined) => {
    search = search ?? searchQuery.value

    const data = search ? {
        search: search,
    } : {}

    router.get(`/chat/${props.chat.id}`, data, {
        preserveState: true,
        replace: true,
    })
}

watch(searchQuery, value => {
    console.log('***** Searching for:', value)

    refreshData(value)
})

console.log('***** Configure Pusher')

const channel = window.Echo.join(`chat.${props.chat.id}`).pusher.channels.channels[`presence-chat.${props.chat.id}`]

const deletedMessageIds = ref<any[]>([])

const isMessageDeleted = (messageId: string | number): boolean => {
    return deletedMessageIds.value.findIndex((id: string | number) => {
        return id.toString() === messageId.toString()
    }) >= 0
}

class MessageIdWrapper {
    messageId: string | number

    constructor(id: string) {
        this.messageId = id
    }
}

onMounted(() => {
    console.log('***** Binding events - onMounted')

    channel.bind('message_added', (e: ChatMessageModel) => {
        console.log('***** message_added *****', e)

        if (!props.messages.links[0].url) {
            // Normally the same user shouldn't be logged in on multiple devices.
            // However, when it's the case, we do not updated table if a new message
            // was added by current user from another device.
            // TODO: we have to find a solution!
            if (currentUserId !== e.user.id) {
                refreshData()
            }
        }
    })

    channel.bind('message_updated', (e: ChatMessageModel) => {
        console.log('***** message_updated *****', e)

        const index = props.messages.data.findIndex((message: ChatMessageModel) => {
            return message.id === e.id
        })

        if (index >= 0) {
            props.messages.data[index].message = e.message
            // There is no need to get back to API and read updated message.
            // Currently, we only update message text. However, if we have to
            // reload message from API (e.g. for permissions update), we can
            // call the route below...
            // window.axios.get(`/message/${e.id}/read`).then((response: any) => {
            //     console.log('***** Message read response', response)
            //
            //     if (response.status === 200) {
            //         props.messages.data[index] = new ChatMessageModel(response.data);
            //     } else {
            //         console.log(`Error reading chat ${e.id}`)
            //     }
            // })
        }
    })

    channel.bind('message_deleted', (e: MessageIdWrapper) => {
        console.log('***** message_deleted *****', e)

        deletedMessageIds.value.push(e.messageId)

        console.log('***** DELETED MESSAGE IDS', deletedMessageIds.value)
    })
})

onBeforeUnmount(() => {
    console.log('***** Unbinding events - onBeforeUnmount')

    channel.unbind('message_added')
    channel.unbind('message_updated')
    channel.unbind('message_deleted')

    window.Echo.leave(`chat.${props.chat.id}`)
})

const chatMessageItems = computed(() => props.messages.data.map((m) => {
        m.chat = props.chat
        return new ChatMessageModel(m)
    }
))

</script>

<template>
    <Head title="Messages"/>

    <AuthenticatedLayout>
        <div class="mx-auto max-w-6xl mt-10 px-4">
            <h1 class="font-light text-3xl mb-4">{{ chat.topic }}</h1>

            <div class="flex items-center justify-normal mb-6">
                <TextInput v-model="searchQuery" class="mr-1 w-64"/>
                <SecondaryButton @click="searchQuery=''" class="ml-1 mr-1">Reset Search</SecondaryButton>
                <SecondaryButton @click="showCreateModal=true" class="ml-1 mr-1">Create</SecondaryButton>
            </div>

            <div class="bg-white rounded-md shadow overflow-x-auto">
                <table class="w-full whitespace-nowrap">
                    <thead>
                    <tr class="text-left font-bold">
                        <th class="pb-4 pt-6 px-6">Created</th>
                        <th class="pb-4 pt-6 px-6">Message</th>
                        <th class="pb-4 pt-6 px-6">User</th>
                        <th class="pb-4 pt-6 px-6">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="message in chatMessageItems" :key="message.id"
                        class="hover:bg-gray-100 focus-within:bg-gray-100"
                        :class="{ 'opacity-25': isMessageDeleted(message.id) }">
                        <ChatMessageRow
                            :chat-message="message"
                            :messageDeleted="isMessageDeleted(message.id)"
                            :deleteButtonDisabled="deleteForm.processing"
                            @edit="editMessage"
                            @delete="deleteMessage"/>
                    </tr>
                    <tr v-if="chatMessageItems.length === 0">
                        <td class="px-6 py-4 border-t text-center" colspan="4">No messages found.</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <pagination class="mt-6" :links="messages.links"/>

            <NavLink :href="returnUrl || route('home')">Back to list</NavLink>
        </div>
        <CreateOrUpdateMessage :show="showCreateModal" :chat="chat" :message="messageBeingEdited"
                               @close="onMessageModalClose"/>
    </AuthenticatedLayout>
</template>

<style scoped>

</style>
