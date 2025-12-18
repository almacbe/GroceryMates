<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch, onMounted, onUnmounted, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const props = defineProps({
    listA: {
        type: String,
        default: '',
    },
    listB: {
        type: String,
        default: '',
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

const page = usePage();
const fieldErrors = computed(() => page.props.errors ?? {});
const generalError = computed(() => fieldErrors.value.lists ?? null);

// Refs for the lists and result to make them reactive
const listA = ref(props.listA);
const listB = ref(props.listB);
const suppressServerSync = ref(false);
let pollingTimer = null;

// Debounce function
const debounce = (func, delay) => {
    let timer = null;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => {
            func.apply(this, args);
        }, delay);
    };
};

const updateListOnServer = (listName, content) => {
    axios.post(route('merge.updateList'), { listName, content, originId: clientId });
};

const debouncedUpdateListA = debounce(content => updateListOnServer('listA', content), 150);
const debouncedUpdateListB = debounce(content => updateListOnServer('listB', content), 150);

watch(listA, (newValue) => {
    if (suppressServerSync.value) return;
    debouncedUpdateListA(newValue);
});

watch(listB, (newValue) => {
    if (suppressServerSync.value) return;
    debouncedUpdateListB(newValue);
});

const getState = () => {
    axios.get(route('merge.state')).then(response => {
        suppressServerSync.value = true;
        listA.value = response.data.listA ?? '';
        listB.value = response.data.listB ?? '';
        setTimeout(() => {
            suppressServerSync.value = false;
        }, 0);
    });
};

onMounted(() => {
    if (window.Echo) {
        window.Echo.private('merge-state').listen('.merge.state.updated', (event) => {
            if (event.originId && event.originId === clientId) return;

            suppressServerSync.value = true;
            if (Object.prototype.hasOwnProperty.call(event.updates ?? {}, 'listA')) {
                listA.value = event.updates.listA ?? '';
            }
            if (Object.prototype.hasOwnProperty.call(event.updates ?? {}, 'listB')) {
                listB.value = event.updates.listB ?? '';
            }
            setTimeout(() => {
                suppressServerSync.value = false;
            }, 0);
        });

        return;
    }

    getState();
    pollingTimer = setInterval(getState, 1000);
});

onUnmounted(() => {
    window.Echo?.leave?.('merge-state');
    if (pollingTimer) clearInterval(pollingTimer);
});

const mergeForm = useForm({
    originId: clientId,
});
const submitMerge = () => {
    Promise.all([
        axios.post(route('merge.updateList'), { listName: 'listA', content: listA.value, originId: clientId }),
        axios.post(route('merge.updateList'), { listName: 'listB', content: listB.value, originId: clientId }),
    ]).finally(() => {
        mergeForm.post(route('merge.store'), {
            preserveScroll: true,
        });
    });
};

const clearState = () => {
    axios.delete(route('merge.clearState')).then(() => {
        suppressServerSync.value = true;
        listA.value = '';
        listB.value = '';
        setTimeout(() => {
            suppressServerSync.value = false;
        }, 0);
    });
};

</script>

<template>
    <Head title="Merge Lists" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Merge Lists</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl px-4">
                <div class="rounded-lg bg-white p-8 shadow">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-semibold text-gray-800">Fusionar listas</h1>
                        <button @click="clearState" class="text-sm text-red-500 hover:underline">Clear</button>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        Pega dos listas exportadas desde INDYA y combina los ingredientes en una sola. Los cambios se ven en tiempo real.
                    </p>

                    <form class="mt-8 space-y-6" @submit.prevent="submitMerge">
                        <div>
                            <label for="listA" class="mb-2 block text-sm font-medium text-gray-700">
                                Lista A
                            </label>
                            <textarea
                                id="listA"
                                v-model="listA"
                                class="h-40 w-full rounded border border-gray-300 p-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                placeholder="[ ] Huevo Crudo: 231g"
                            />
                            <p v-if="fieldErrors.listA" class="mt-2 text-sm text-red-600">
                                {{ fieldErrors.listA }}
                            </p>
                        </div>

                        <div>
                            <label for="listB" class="mb-2 block text-sm font-medium text-gray-700">
                                Lista B
                            </label>
                            <textarea
                                id="listB"
                                v-model="listB"
                                class="h-40 w-full rounded border border-gray-300 p-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                placeholder="[ ] Pan Integral: 170g"
                            />
                            <p v-if="fieldErrors.listB" class="mt-2 text-sm text-red-600">
                                {{ fieldErrors.listB }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between">
                            <p v-if="generalError" class="text-sm text-red-600">
                                {{ generalError }}
                            </p>
                            <button
                                type="submit"
                                class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-indigo-300"
                                :disabled="mergeForm.processing"
                            >
                                {{ mergeForm.processing ? 'Fusionando…' : 'Merge' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
