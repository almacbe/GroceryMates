<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Service Status" />

    <div class="flex min-h-screen items-center justify-center bg-gray-100">
        <div class="rounded border border-green-400 bg-white px-8 py-6 text-center shadow">
            <p class="text-lg font-semibold text-green-600">✅ The service is running.</p>
            <p class="mt-2 text-sm text-gray-600">
                Laravel + Inertia deployment check page.
            </p>
        </div>
    </div>
</template>
