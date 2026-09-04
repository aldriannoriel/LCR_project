<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, computed } from 'vue';
import { axios, echo } from '../../lib/echo';
import { useAuthStore } from '../../stores/auth';
import {
  MessageSquare,
  Send,
  Paperclip,
  Search,
  User,
  CheckCheck,
  Plus,
  X,
  Phone,
  Mail,
  Building,
  RefreshCw,
  Clock,
  ExternalLink
} from 'lucide-vue-next';

const auth = useAuthStore();

const conversations = ref([]);
const activeConversation = ref(null);
const messages = ref([]);
const messageText = ref('');
const attachmentFile = ref(null);

const contacts = ref([]);
const showNewChatModal = ref(false);
const searchQuery = ref('');
const contactsSearch = ref('');
const loadingConversations = ref(false);
const loadingMessages = ref(false);
const sending = ref(false);

const messagesContainer = ref(null);

const filteredConversations = computed(() => {
  if (!searchQuery.value.trim()) return conversations.value;
  const q = searchQuery.value.toLowerCase();
  return conversations.value.filter((c) => {
    return c.other_user?.name?.toLowerCase().includes(q) || c.other_user?.email?.toLowerCase().includes(q);
  });
});

const filteredContacts = computed(() => {
  if (!contactsSearch.value.trim()) return contacts.value;
  const q = contactsSearch.value.toLowerCase();
  return contacts.value.filter((c) => {
    return c.name?.toLowerCase().includes(q) || c.email?.toLowerCase().includes(q) || c.phone_number?.includes(q);
  });
});

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
};

const fetchConversations = async () => {
  loadingConversations.value = true;
  try {
    const res = await axios.get('/chat/conversations', {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    conversations.value = res.data;
    if (conversations.value.length && !activeConversation.value) {
      selectConversation(conversations.value[0]);
    }
  } catch (err) {
    console.error('Failed to load conversations:', err);
  } finally {
    loadingConversations.value = false;
  }
};

const fetchContacts = async () => {
  try {
    const res = await axios.get('/chat/contacts', {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    contacts.value = res.data;
  } catch (_) {}
};

const selectConversation = async (conv) => {
  // Leave previous conversation channel if subscribed
  if (activeConversation.value && echo) {
    echo.leave(`chat.${activeConversation.value.id}`);
  }

  activeConversation.value = conv;
  conv.unread_count = 0;
  loadingMessages.value = true;

  try {
    const res = await axios.get(`/chat/conversations/${conv.id}/messages`, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    messages.value = res.data;
    scrollToBottom();

    // Subscribe to real-time chat channel
    if (echo) {
      echo.private(`chat.${conv.id}`)
        .listen('MessageSent', (e) => {
          if (e.message && e.conversation_id === activeConversation.value?.id) {
            messages.value.push(e.message);
            scrollToBottom();
          }
        });
    }
  } catch (err) {
    console.error('Failed to load messages:', err);
  } finally {
    loadingMessages.value = false;
  }
};

const startChatWith = async (contact) => {
  try {
    const res = await axios.post('/chat/conversations', {
      recipient_id: contact.id,
    }, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });

    showNewChatModal.value = false;
    await fetchConversations();

    const found = conversations.value.find((c) => c.id === res.data.id);
    if (found) {
      selectConversation(found);
    } else {
      selectConversation({
        id: res.data.id,
        other_user: contact,
        last_message: null,
      });
    }
  } catch (err) {
    console.error('Failed to start chat:', err);
  }
};

const handleAttachment = (e) => {
  attachmentFile.value = e.target.files[0] || null;
};

const sendMessage = async () => {
  if ((!messageText.value.trim() && !attachmentFile.value) || !activeConversation.value) return;

  const text = messageText.value.trim();
  const file = attachmentFile.value;

  messageText.value = '';
  attachmentFile.value = null;
  sending.value = true;

  try {
    const formData = new FormData();
    formData.append('message', text);
    if (file) formData.append('attachment', file);

    const res = await axios.post(`/chat/conversations/${activeConversation.value.id}/messages`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        Authorization: `Bearer ${auth.token}`,
      },
    });

    messages.value.push(res.data);
    if (activeConversation.value) {
      activeConversation.value.last_message = res.data;
    }
    scrollToBottom();
  } catch (err) {
    console.error('Failed to send message:', err);
    messageText.value = text;
  } finally {
    sending.value = false;
  }
};

onMounted(() => {
  fetchConversations();
  fetchContacts();

  // Listen on user channel for new incoming messages across any conversation
  if (echo && auth.user?.id) {
    echo.private(`user.${auth.user.id}`)
      .listen('MessageSent', (e) => {
        const conv = conversations.value.find((c) => c.id === e.conversation_id);
        if (conv) {
          conv.last_message = e.message;
          if (activeConversation.value?.id !== e.conversation_id) {
            conv.unread_count = (conv.unread_count || 0) + 1;
          }
        } else {
          fetchConversations();
        }
      });
  }
});

onBeforeUnmount(() => {
  if (activeConversation.value && echo) {
    echo.leave(`chat.${activeConversation.value.id}`);
  }
  if (auth.user?.id && echo) {
    echo.leave(`user.${auth.user.id}`);
  }
});
</script>

<template>
  <div class="h-[calc(100vh-4rem)] lg:h-screen flex flex-col bg-slate-100">
    <!-- Header -->
    <div class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <MessageSquare class="h-6 w-6 text-teal-600" />
        <div>
          <h1 class="text-xl font-black text-slate-900">Operations Messaging & Chat</h1>
          <p class="text-xs text-slate-500">Real-time coordination between dispatchers, riders, and merchants.</p>
        </div>
      </div>

      <button
        @click="showNewChatModal = true"
        class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-xs font-bold uppercase tracking-wider text-white shadow hover:bg-teal-500 transition"
      >
        <Plus class="h-4 w-4" />
        Start New Chat
      </button>
    </div>

    <!-- Split Pane Body -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Left: Conversations List -->
      <div class="w-80 md:w-96 border-r border-slate-200 bg-white flex flex-col shrink-0">
        <!-- Search -->
        <div class="p-3 border-b border-slate-200">
          <div class="relative">
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search conversations..."
              class="w-full rounded-lg border border-slate-300 bg-slate-50 pl-9 pr-3 py-1.5 text-xs text-slate-900 focus:bg-white focus:outline-none"
            />
          </div>
        </div>

        <!-- Threads list -->
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
          <div
            v-for="c in filteredConversations"
            :key="c.id"
            @click="selectConversation(c)"
            class="p-4 cursor-pointer transition flex items-start justify-between hover:bg-slate-50"
            :class="activeConversation?.id === c.id ? 'bg-teal-50/70 border-l-4 border-teal-600' : ''"
          >
            <div class="flex items-start gap-3 min-w-0">
              <div class="h-10 w-10 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0">
                {{ c.other_user?.name?.charAt(0) || 'U' }}
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                  <p class="text-sm font-bold text-slate-900 truncate">{{ c.other_user?.name }}</p>
                </div>
                <span class="inline-block text-[10px] font-semibold text-teal-700 uppercase tracking-wider bg-teal-50 px-1.5 py-0.2 rounded">
                  {{ c.other_user?.roles?.[0]?.name || 'User' }}
                </span>
                <p class="text-xs text-slate-500 truncate mt-1">
                  {{ c.last_message ? c.last_message.message : 'No messages yet' }}
                </p>
              </div>
            </div>

            <div class="flex flex-col items-end shrink-0 ml-2">
              <span class="text-[10px] text-slate-400">
                {{ c.last_message ? new Date(c.last_message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '' }}
              </span>
              <span
                v-if="c.unread_count > 0"
                class="mt-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-teal-600 px-1 text-[10px] font-bold text-white"
              >
                {{ c.unread_count }}
              </span>
            </div>
          </div>

          <div v-if="!filteredConversations.length && !loadingConversations" class="p-8 text-center text-xs text-slate-400">
            No conversations found.
          </div>
        </div>
      </div>

      <!-- Right: Active Chat Timeline -->
      <div class="flex-1 flex flex-col bg-slate-50">
        <!-- Thread Header -->
        <div v-if="activeConversation" class="bg-white border-b border-slate-200 px-6 py-3.5 flex items-center justify-between shrink-0">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs">
              {{ activeConversation.other_user?.name?.charAt(0) || 'U' }}
            </div>
            <div>
              <p class="text-sm font-bold text-slate-900">{{ activeConversation.other_user?.name }}</p>
              <p class="text-xs text-slate-500">{{ activeConversation.other_user?.email }} • {{ activeConversation.other_user?.roles?.[0]?.name || 'User' }}</p>
            </div>
          </div>
        </div>

        <!-- Messages Stream -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-4">
          <div v-if="!activeConversation" class="h-full flex flex-col items-center justify-center text-slate-400">
            <MessageSquare class="h-12 w-12 text-slate-300 mb-2" />
            <p class="text-sm font-bold">Select a conversation or start a new chat.</p>
          </div>

          <div
            v-for="m in messages"
            :key="m.id"
            class="flex flex-col"
            :class="m.sender_id === auth.user?.id ? 'items-end' : 'items-start'"
          >
            <div class="flex items-center gap-2 mb-1">
              <span class="text-[10px] text-slate-400 font-semibold">{{ m.sender?.name }}</span>
              <span class="text-[10px] text-slate-400">{{ new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
            </div>

            <div
              class="max-w-md rounded-2xl px-4 py-2.5 text-sm shadow-xs"
              :class="m.sender_id === auth.user?.id ? 'bg-teal-600 text-white rounded-br-none' : 'bg-white text-slate-800 border border-slate-200 rounded-bl-none'"
            >
              <p class="leading-relaxed whitespace-pre-wrap">{{ m.message }}</p>

              <div v-if="m.attachment_path" class="mt-2 pt-2 border-t border-white/20">
                <a
                  :href="`http://localhost:8000/storage/${m.attachment_path}`"
                  target="_blank"
                  class="inline-flex items-center gap-1 text-xs font-bold underline"
                  :class="m.sender_id === auth.user?.id ? 'text-teal-100' : 'text-teal-700'"
                >
                  <Paperclip class="h-3 w-3" />
                  View File Attachment
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- Input Box -->
        <div v-if="activeConversation" class="p-4 bg-white border-t border-slate-200 shrink-0">
          <form @submit.prevent="sendMessage" class="flex items-center gap-3">
            <label class="cursor-pointer text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-100">
              <input type="file" class="hidden" @change="handleAttachment" />
              <Paperclip class="h-5 w-5" :class="attachmentFile ? 'text-teal-600' : ''" />
            </label>

            <span v-if="attachmentFile" class="text-xs text-teal-700 font-semibold truncate max-w-[120px]">
              {{ attachmentFile.name }}
            </span>

            <input
              v-model="messageText"
              type="text"
              placeholder="Type your message here... (Press Enter to send)"
              class="flex-1 rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-teal-500 focus:outline-none"
            />

            <button
              type="submit"
              :disabled="sending || (!messageText.trim() && !attachmentFile)"
              class="rounded-xl bg-teal-600 p-2.5 text-white shadow hover:bg-teal-500 disabled:opacity-40 transition"
            >
              <Send class="h-5 w-5" />
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- New Chat Contacts Modal -->
    <div
      v-if="showNewChatModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
      @click.self="showNewChatModal = false"
    >
      <div class="w-full max-w-md max-h-[80vh] flex flex-col rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3">
          <h2 class="text-lg font-black text-slate-900">Start a Conversation</h2>
          <button @click="showNewChatModal = false" class="text-slate-400 hover:text-slate-600">
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="mt-3">
          <input
            v-model="contactsSearch"
            type="text"
            placeholder="Search contacts by name or email..."
            class="w-full rounded-lg border border-slate-300 p-2 text-xs"
          />
        </div>

        <div class="mt-3 flex-1 overflow-y-auto divide-y divide-slate-100 max-h-96">
          <div
            v-for="contact in filteredContacts"
            :key="contact.id"
            @click="startChatWith(contact)"
            class="p-3 flex items-center justify-between hover:bg-slate-50 cursor-pointer rounded-lg transition"
          >
            <div>
              <p class="text-sm font-bold text-slate-900">{{ contact.name }}</p>
              <p class="text-xs text-slate-500">{{ contact.email }}</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider bg-slate-100 px-2 py-0.5 rounded text-slate-700">
              {{ contact.roles?.[0]?.name || 'User' }}
            </span>
          </div>

          <div v-if="!filteredContacts.length" class="p-6 text-center text-xs text-slate-400">
            No contacts available.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
