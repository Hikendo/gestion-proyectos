<script setup>
import RolesFeature from '../features/admin/roles/RolesFeature.vue';
import UsersFeature from '../features/admin/users/UsersFeature.vue';
</script>

<template>
  <section class="page-grid">
    <UsersFeature />
    <RolesFeature />
  </section>
</template>
   │
 3 │ import vuetify from "vite-plugin-vuetify";
   │                     ──────────┬──────────
   │                               ╰──────────── Module not found, treating it as an external dependency
───╯

failed to load config from /app/vite.config.js
error when starting dev server:
Error [ERR_MODULE_NOT_FOUND]: Cannot find package 'vite-plugin-vuetify' imported from /app/node_modules/.vite-temp/vite.config.js.timestamp-1779825455407-df55cd8ad450c.mjs
    at Object.getPackageJSONURL (node:internal/modules/package_json_reader:314:9)
    at packageResolve (node:internal/modules/esm/resolve:768:81)
    at moduleResolve (node:internal/modules/esm/resolve:855:18)
    at defaultResolve (node:internal/modules/esm/resolve:985:11)
    at #cachedDefaultResolve (node:internal/modules/esm/loader:747:20)
    at ModuleLoader.resolve (node:internal/modules/esm/loader:724:38)
    at ModuleLoader.getModuleJobForImport (node:internal/modules/esm/loader:320:38)
    at ModuleJob._link (node:internal/modules/esm/module_job:182:49) {
  code: 'ERR_MODULE_NOT_FOUND'
}
