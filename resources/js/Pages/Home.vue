<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {Head, useForm} from '@inertiajs/vue3'

import ChatRow from '@/Pages/Home/ChatRow.vue'
import CreateOrUpdateChat from "@/Pages/Home/CreateOrUpdateChat.vue"
import Pagination from '@/Components/Pagination.vue'
import ChatModel from '@/Models/ChatModel'
import {computed, onBeforeUnmount, onMounted, ref, watch} from 'vue'
import debounce from "lodash/debounce";

import {usePage, router} from '@inertiajs/vue3'
import TextInput from "@/Components/TextInput.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps<{
  auth: any,
  chats: {
    data: ChatModel[],
    links: any[]
  },
  filters: {
    search: string | null
  },
}>()

const searchQuery = ref<string>(props.filters.search || '')

const showCreateModal = ref<boolean>(false)

const chatBeingEdited = ref<ChatModel | undefined>(undefined)

const currentUserId = props.auth.user.id

const refreshData = (search: string | undefined = undefined) => {
  search = search ?? searchQuery.value

  const data = search ? {
    search: search
  } : {}

  router.get(route('home'), data, {
    preserveState: true,
    replace: true,
  })
}

const joinChat = (chat: ChatModel) => {
  if (!actionForm.processing) {
    console.log(`Joining chat ${chat.id}`)
    actionForm.put(`/chat/${chat.id}/join`, {
      preserveScroll: true
    })
  }
}

const leaveChat = (chat: ChatModel) => {
  if (!actionForm.processing) {
    console.log(`Leaving chat ${chat.id}`)
    actionForm.put(`/chat/${chat.id}/leave`, {
      preserveScroll: true
    })
  }
}

const editChat = (chat: ChatModel) => {
  chatBeingEdited.value = chat
  showCreateModal.value = true
}

const actionForm = useForm({
  id: ''
})

const deleteChat = (chat: ChatModel) => {
  if (!actionForm.processing) {
    console.log(`Deleting chat ${chat.id}`)
    actionForm.delete(`/chat/${chat.id}`, {
      preserveScroll: true
    })
  }
}

const onChatModalClose = () => {
  chatBeingEdited.value = undefined
  showCreateModal.value = false
}

watch(searchQuery, value => {
  search(value)
})

const search = debounce((value: string) => {
  refreshData(value)
}, 500)

const channel = window.Echo.private('chat.updates').pusher.channels.channels['private-chat.updates']

// let channel = window.Pusher.channels['private-chat.updates']

const deletedChatIds = ref<any[]>([])

const isChatDeleted = (chatId: string | number): boolean => {
  return deletedChatIds.value.findIndex((id: string | number) => {
    return id.toString() === chatId.toString()
  }) >= 0
}

// TODO: Documented version doesn't work...
// let coreChannel = window.Echo.private('chat.updates');

// TODO: move it to dedicated model TS file.
class ChatIdWrapper {
  chatId: string | number

  constructor(id: string) {
    this.chatId = id
  }
}

onMounted(() => {
  console.log('Binding events - onMounted')

  channel.bind('chat_created', (e: ChatModel) => {
    // console.log('***** chat_created *****', e)

    if (!props.chats.links[0].url) {
      // Normally the same user shouldn't be logged in on multiple devices.
      // However, when it's the case, we do not updated table if a new chat
      // was added by current user from another device.
      // TODO: we have to find a solution!
      if (currentUserId !== e.users[0].id) {
        refreshData()
      }
    }
  })

  channel.bind('chat_updated', (e: ChatModel) => {
    // console.log('***** chat_updated *****', e)

    const index = props.chats.data.findIndex((chat: ChatModel) => {
      return chat.id === e.id
    })

    if (index >= 0) {
      window.axios.get(`/chat/${e.id}/read`).then((response: any) => {
        // console.log('Chat item response', response)

        if (response.status === 200) {
          props.chats.data[index] = new ChatModel(response.data);
        } else {
          console.log(`Error reading chat ${e.id}`)
        }
      })
    }
  })

  channel.bind('chat_deleted', (e: ChatIdWrapper) => {
    // console.log('***** chat_deleted *****', e)

    deletedChatIds.value.push(e.chatId)
  })

  // TODO: Documented version doesn't work...
  // coreChannel.listen('chat_updated', (e: ChatModel) => {
  //     console.log('***** Core listener!', e)
  // });
})

onBeforeUnmount(() => {
  console.log('Unbinding events - onBeforeUnmount')

  channel.unbind('chat_created')
  channel.unbind('chat_updated')
  channel.unbind('chat_deleted')

  window.Echo.leaveChannel('private-chat.updates')
})

const chatItems = computed(() => props.chats.data.map(ch => new ChatModel(ch)))

</script>

<template>
  <Head title="Chat List"/>

  <AuthenticatedLayout>
    <div class="mx-auto max-w-6xl mt-10 px-4">
      <h1 class="font-light text-3xl mb-4">Chat List</h1>

      <div class="flex items-center justify-normal mb-6">
        <TextInput v-model="searchQuery" placeholder="Search in topics, messages or users..." class="mr-1 w-96"/>
        <SecondaryButton @click="searchQuery=''" class="ml-1 mr-1">Reset Search</SecondaryButton>
        <SecondaryButton @click="showCreateModal=true" class="ml-1 mr-1">Create</SecondaryButton>
      </div>

      <div class="bg-white rounded-md shadow overflow-x-auto">
        <table class="w-full table-fixed">
          <thead>
          <tr class="text-left font-bold">
            <th class="pb-4 pt-6 px-6">Created</th>
            <th class="pb-4 pt-6 px-6">Topic</th>
            <th class="pb-4 pt-6 px-6">Users</th>
            <th class="pb-4 pt-6 px-6">Actions</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="chat in chatItems"
              :key="chat.id"
              class="hover:bg-gray-100 focus-within:bg-gray-100"
              :class="{ 'opacity-25': isChatDeleted(chat.id) }"
          >
            <ChatRow
                :chat="chat" :return-url="usePage().url"
                :canListMessages="!!chat.can.listMessages"
                :chatDeleted="isChatDeleted(chat.id)"
                :actionButtonsDisabled="actionForm.processing"
                @join="joinChat"
                @leave="leaveChat"
                @edit="editChat"
                @delete="deleteChat"
            />
          </tr>
          <tr v-if="chatItems.length === 0">
            <td class="px-6 py-4 border-t text-center" colspan="4">No chats found.</td>
          </tr>
          </tbody>
        </table>
      </div>

      <pagination class="mt-6" :links="chats.links"/>
    </div>
    <CreateOrUpdateChat :show="showCreateModal" :chat="chatBeingEdited" @close="onChatModalClose"/>
  </AuthenticatedLayout>
</template>

<style scoped>

</style>
