<script setup lang="ts">
import type ChatModel from '@/Models/ChatModel.js'
import {computed, ComputedRef} from 'vue';
import {Link} from '@inertiajs/vue3';

import UserModel from "@/Models/User";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";

const props = defineProps<{
    chat: ChatModel,
    canListMessages: boolean,
    chatDeleted: boolean,
    returnUrl?: string,
    actionButtonsDisabled: boolean,
}>()

const users = computed(() => {
    if (props.chat) {
        console.log('***** users getter', props.chat.users)
        return props.chat.users
    }
    return []
})

const firstUser = computed(() => {
    if (props.chat.users.length > 0) {
        return props.chat.users[0]
    }
    return null
})

const moreUsers = computed(() => {
    if (props.chat.users.length > 0) {
        return props.chat.users.slice(1, 3)
    }
    return null
})

const andMoreUsersCount = computed(() => {
    if (props.chat.users.length > 3) {
        return props.chat.users.length - 3
    }
    return null
})

const emitEvent = defineEmits<{
    join: [value: ChatModel],
    leave: [value: ChatModel],
    edit: [value: ChatModel],
    delete: [value: ChatModel]
}>()

</script>

<template>
    <td class="border-t">
        <Link
            v-if="!chatDeleted && canListMessages"
            class="flex items-center px-6 py-4 focus:text-indigo-500"
            :href="route('chat', {
          chat: chat.id,
          returnUrl: returnUrl
        })"
        >
            {{ chat.getCreatedAt() }}
        </Link>
        <div v-else class="flex items-center px-6 py-4 focus:text-indigo-500">
            {{ chat.getCreatedAt() }}
        </div>
    </td>
    <td class="border-t">
        <Link
            v-if="!chatDeleted && canListMessages"
            class="flex items-center px-6 py-4 focus:text-indigo-500"
            :href="route('chat', {
          chat: chat.id,
          returnUrl: returnUrl
        })"
        >
            {{ chat.topic }}
        </Link>
        <div v-else class="flex items-center px-6 py-4 focus:text-indigo-500">
            {{ chat.topic }}
        </div>
    </td>
    <td class="border-t">
        <div class="flex items-center px-6 py-4 focus:text-indigo-500">
            <div>
                {{ firstUser ? firstUser.name : 'No users' }}
                <ul v-if="moreUsers">
                    <li v-for="user in moreUsers" :key="user.id">
                        {{ user.name }}
                    </li>
                </ul>
                <div v-if="andMoreUsersCount" class="italic">
                    ... and {{ andMoreUsersCount }} more
                </div>
            </div>
        </div>
    </td>
    <td v-if="!chatDeleted" class="w-px border-t">
        <SecondaryButton
            v-if="chat.can.join"
            @click="emitEvent('join', chat)"
            class="ml-1 mr-1 mb-1 mt-1">Join
        </SecondaryButton>
        <SecondaryButton
            v-if="chat.can.leave"
            @click="emitEvent('leave', chat)"
            class="ml-1 mr-1 mb-1 mt-1">Leave
        </SecondaryButton>
        <SecondaryButton
            v-if="chat.can.update"
            @click="emitEvent('edit', chat)"
            class="ml-1 mr-1 mb-1">Edit
        </SecondaryButton>
        <DangerButton
            v-if="chat.can.delete"
            @click="emitEvent('delete', chat)"
            :disabled="actionButtonsDisabled"
            :class="{ 'opacity-25': actionButtonsDisabled }"
            class="ml-1 mr-2 mb-1">Delete
        </DangerButton>
    </td>
    <td v-else class="w-px border-t"></td>
</template>

<style scoped>

</style>
