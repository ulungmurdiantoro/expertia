<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    role: {
        type: Object,
        required: true,
    },
    permissions: {
        type: Array,
        default: () => [],
    },
    permission_groups: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    permissions: [...(props.role.permissions || [])],
});

const selected = (permission) => form.permissions.includes(permission);

const togglePermission = (permission) => {
    if (selected(permission)) {
        form.permissions = form.permissions.filter((item) => item !== permission);
        return;
    }

    form.permissions = [...form.permissions, permission];
};

const toggleGroup = (group) => {
    const groupPermissions = group.items || [];
    const allSelected = groupPermissions.every((permission) => selected(permission));

    if (allSelected) {
        form.permissions = form.permissions.filter((permission) => !groupPermissions.includes(permission));
        return;
    }

    const merged = new Set([...form.permissions, ...groupPermissions]);
    form.permissions = [...merged];
};

const submit = () => {
    form.put(route('admin.roles.update', props.role.id));
};
</script>

<template>
    <Head :title="`Edit Permission - ${role.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Permission: {{ role.name }}</h2>
                <Link :href="route('admin.roles.index')" class="rounded bg-gray-700 px-3 py-2 text-sm text-white hover:bg-gray-600">
                    Kembali
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-screen-2xl space-y-4 sm:px-6 lg:px-8">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-600">
                        Total permission terpilih: <strong>{{ form.permissions.length }}</strong>
                    </p>
                </div>

                <div v-for="group in permission_groups" :key="group.label" class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-800">{{ group.label }}</h3>
                        <button
                            type="button"
                            @click="toggleGroup(group)"
                            class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200"
                        >
                            Toggle Group
                        </button>
                    </div>

                    <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        <label
                            v-for="permission in group.items"
                            :key="permission"
                            class="flex items-start gap-2 rounded border border-gray-200 p-2 text-sm"
                        >
                            <input
                                type="checkbox"
                                :checked="selected(permission)"
                                @change="togglePermission(permission)"
                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-gray-700">{{ permission }}</span>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <button
                        type="button"
                        @click="form.permissions = []"
                        class="rounded bg-gray-600 px-3 py-2 text-sm text-white hover:bg-gray-500"
                    >
                        Clear
                    </button>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="rounded bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Simpan Permission
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

