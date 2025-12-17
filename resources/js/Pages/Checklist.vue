<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const clientId = (() => {
    const storageKey = 'grocery-mates:client-id';
    const existing = sessionStorage.getItem(storageKey);
    if (existing) {
        return existing;
    }

    const id = globalThis.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random().toString(16).slice(2)}`;
    sessionStorage.setItem(storageKey, id);
    return id;
})();

const items = ref(props.items.map(item => ({ ...item })));

const checkedCount = computed(() => items.value.filter(item => item.checked).length);
const totalCount = computed(() => items.value.length);

const sortedItems = computed(() => {
    return [...items.value].sort((a, b) => {
        if (a.checked && !b.checked) return 1;
        if (!a.checked && b.checked) return -1;
        return 0;
    });
});

const updateItemOnServer = (id, checked) => {
    axios.post(route('merge.checklist.update'), { id, checked, originId: clientId });
};

const toggleItem = (item, checked) => {
    item.checked = checked;
    updateItemOnServer(item.id, checked);
};

onMounted(() => {
    if (!window.Echo) return;

    window.Echo.private('merge-checklist')
        .listen('.merge.checklist.replaced', (event) => {
            if (event.originId && event.originId === clientId) return;
            items.value = (event.items ?? []).map(item => ({ ...item }));
        })
        .listen('.merge.checklist.item.updated', (event) => {
            if (event.originId && event.originId === clientId) return;

            const target = items.value.find(item => item.id === event.id);
            if (!target) return;
            target.checked = Boolean(event.checked);
        });
});

onUnmounted(() => {
    window.Echo?.leave?.('merge-checklist');
});
</script>

<template>
    <Head title="Checklist" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Your Merged Grocery List</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Grocery Checklist</h3>
                            <span class="text-sm text-gray-500">{{ checkedCount }} / {{ totalCount }} items</span>
                        </div>
                        <ul class="divide-y divide-gray-200">
                            <li v-for="item in sortedItems" :key="item.id" class="py-4 flex items-center">
                                <input
                                    :id="`item-${item.id}`"
                                    type="checkbox"
                                    class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    :checked="item.checked"
                                    @change="toggleItem(item, $event.target.checked)"
                                />
                                <label
                                    :for="`item-${item.id}`"
                                    class="ml-3 block text-gray-900"
                                    :class="{ 'line-through text-gray-500': item.checked }"
                                >
                                    <span class="text-lg">{{ item.text }}</span>
                                </label>
                            </li>
                        </ul>
                        <div class="mt-6 text-center">
                            <Link
                                :href="route('merge.index')"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                Back to Merge
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
