<script setup lang="ts">
import ChatMessageModel from "@/Models/ChatMessageModel";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

defineProps<{
  chatMessage: ChatMessageModel,
  messageDeleted: boolean,
  deleteButtonDisabled: boolean,
  showUserConnected: boolean
}>()

const emitEvent = defineEmits<{
  edit: [value: ChatMessageModel],
  delete: [value: ChatMessageModel]
}>()

</script>

<template>
  <td class="border-t">
    <div class="flex items-center px-6 py-4 focus:text-indigo-500">
      {{ chatMessage.getCreatedAt() }}
    </div>
  </td>
  <td class="border-t">
    <div class="flex items-center px-6 py-4 focus:text-indigo-500">
      {{ chatMessage.message }}
    </div>
  </td>
  <td class="border-t">
    <div class="flex items-center px-6 py-4 focus:text-indigo-500">
      {{ chatMessage.user.name }}
      <span v-if="showUserConnected">&nbsp;*</span>
    </div>
  </td>
  <td v-if="!messageDeleted" class="w-px border-t">
    <SecondaryButton
        v-if="chatMessage.can.update"
        @click="emitEvent('edit', chatMessage)"
        class="ml-1 mr-1">Edit
    </SecondaryButton>
    <DangerButton
        v-if="chatMessage.can.delete"
        @click="emitEvent('delete', chatMessage)"
        :disabled="deleteButtonDisabled"
        :class="{ 'opacity-25': deleteButtonDisabled }"
        class="ml-1 mr-2">Delete
    </DangerButton>
  </td>
  <td v-else class="w-px border-t"></td>
</template>

<style scoped>

</style>
