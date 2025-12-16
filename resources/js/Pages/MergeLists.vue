<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    listA: {
        type: String,
        default: '',
    },
    listB: {
        type: String,
        default: '',
    },
    result: {
        type: String,
        default: '',
    },
});

const form = useForm({
    listA: props.listA ?? '',
    listB: props.listB ?? '',
});

watch(
    () => [props.listA, props.listB],
    ([nextA, nextB]) => {
        form.listA = nextA ?? '';
        form.listB = nextB ?? '';
    },
    { immediate: true },
);

const page = usePage();

const fieldErrors = computed(() => page.props.errors ?? {});

const generalError = computed(() => fieldErrors.value.lists ?? null);

const submit = () => {
    form.post(route('merge.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Merge Lists" />

    <div class="min-h-screen bg-gray-100 py-12">
        <div class="mx-auto max-w-4xl px-4">
            <div class="rounded-lg bg-white p-8 shadow">
                <h1 class="text-2xl font-semibold text-gray-800">Fusionar listas</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Pega dos listas exportadas desde INDYA y combina los ingredientes en una sola.
                </p>

                <form class="mt-8 space-y-6" @submit.prevent="submit">
                    <div>
                        <label for="listA" class="mb-2 block text-sm font-medium text-gray-700">
                            Lista A
                        </label>
                        <textarea
                            id="listA"
                            v-model="form.listA"
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
                            v-model="form.listB"
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
                            :disabled="form.processing"
                        >
                            {{ form.processing ? 'Fusionando…' : 'Merge' }}
                        </button>
                    </div>
                </form>

                <div class="mt-8">
                    <label for="result" class="mb-2 block text-sm font-medium text-gray-700">
                        Resultado (gramos + medidas caseras)
                    </label>
                    <p class="text-xs text-gray-500">
                        Cada ingrediente incluye la equivalencia aproximada en cucharadas y tazas.
                    </p>
                    <textarea
                        id="result"
                        class="mt-2 h-48 w-full cursor-text rounded border border-gray-300 bg-gray-50 p-3 text-sm text-gray-800 focus:outline-none"
                        :value="props.result"
                        readonly
                    />
                </div>
            </div>
        </div>
    </div>
</template>
