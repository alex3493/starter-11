<script setup lang="ts">

import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import ChatModel from "@/Models/ChatModel";
import {computed, watch, ref, nextTick} from "vue";

const props = defineProps<{
  show?: boolean,
  chat: ChatModel | undefined,
}>()

const inEditMode = computed(() => {
  return props.chat !== undefined
})

const emit = defineEmits(['close'])

const form = useForm({
  topic: ''
})

const topicInput = ref<HTMLInputElement | null>(null);

watch(props, value => {
  if (value.chat) {
    form.topic = value.chat.topic
  }
  if (value.show) {
    nextTick(() => topicInput.value?.focus())
  }
})

const submit = () => {
  if (inEditMode.value) {
    // Update existing chat.
    form.patch(`/chat/${props.chat?.id}`, {
      preserveScroll: true,
      onSuccess() {
        emit('close')
        form.topic = ''
      },
      onError(errors) {
        console.log('Chat update errors', errors)
      }
    })
  } else {
    // New chat.
    form.post('/chats', {
      onSuccess() {
        emit('close')
        form.topic = ''
      },
      onError(errors) {
        console.log('Chat create errors', errors)
      }
    })
  }
}

const closeModal = () => {
  emit('close')
  form.topic = ''
}

const clearErrors = (field: any) => {
  form.clearErrors(field)
}

</script>

<template>
  <Modal :show="show" @close="emit('close')">
    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
      <form @submit.prevent="submit">
        <div class="sm:flex sm:items-start">
          <ApplicationLogo class="w-20 h-20 fill-current text-gray-500"/>
          <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
            <h3 class="text-base font-semibold leading-6 text-gray-900" id="modal-title">
              {{ inEditMode ? 'Update Chat' : 'Create Chat' }}
            </h3>
            <div class="mt-2 mb-4">
              <div>
                <InputLabel for="topic" value="Topic"/>

                <TextInput
                    id="topic"
                    type="text"
                    class="mt-1 block w-full"
                    ref="topicInput"
                    v-model="form.topic"
                    required
                    autocomplete="name"
                    @update:model-value="clearErrors('topic')"
                />

                <InputError class="mt-2" :message="form.errors.topic"/>
              </div>
            </div>

          </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
          <PrimaryButton
              :disabled="form.processing"
              class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">
            {{ inEditMode ? 'Update' : 'Create' }}
          </PrimaryButton>
          <SecondaryButton
              @click="closeModal"
              :class="{ 'opacity-25': form.processing }"
              class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
            Cancel
          </SecondaryButton>
        </div>
      </form>
    </div>
  </Modal>
</template>

<style scoped>

</style>
