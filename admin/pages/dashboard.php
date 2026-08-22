<!-- Independent Scrollable Main Content Area -->
<main class="flex-1 overflow-y-auto h-[calc(100vh-65px)] flex flex-col justify-between">

    <div class="p-4 sm:p-6 lg:p-8">
        <!-- Page Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Overview Dashboard</h2>
                <p class="text-sm text-slate-500">Manage recent updates and contents for bishwas.org</p>
            </div>
            <div>
                <button onclick="openContentModal()"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-lg shadow transition-all">
                    <i class="fa-solid fa-pen-to-square"></i>
                    Add New Content
                </button>
            </div>
        </div>

        <!-- Analytics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase">Total Projects</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">24</h3>
                </div>
                <div
                    class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase">Published Blogs</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">142</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-newspaper"></i>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase">Total Donations</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">$8,500</h3>
                </div>
                <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-sack-dollar"></i>
                </div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase">Volunteers</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">56</h3>
                </div>
                <div
                    class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </div>

        <!-- Content Table -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
            <div class="p-5 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Recent Contents</h3>
                <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Live
                    Data</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead
                        class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3.5">Title</th>
                            <th class="px-6 py-3.5">Category</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5">Date</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr class="hover:bg-slate-50/50" id="row-1">
                            <td class="px-6 py-4 font-semibold text-slate-800">Winter Clothing Drive 2026</td>
                            <td class="px-6 py-4"><span
                                    class="bg-slate-100 text-slate-700 text-xs px-2.5 py-1 rounded">Project</span></td>
                            <td class="px-6 py-4"><span
                                    class="text-emerald-600 bg-emerald-50 text-xs font-semibold px-2 py-0.5 rounded">Published</span>
                            </td>
                            <td class="px-6 py-4 text-xs">Aug 15, 2026</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button class="text-slate-400 hover:text-emerald-600 p-1"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button onclick="openDeleteModal('Winter Clothing Drive 2026')"
                                    class="text-slate-400 hover:text-rose-600 p-1"><i
                                        class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/50" id="row-2">
                            <td class="px-6 py-4 font-semibold text-slate-800">Free Education Material Distribution</td>
                            <td class="px-6 py-4"><span
                                    class="bg-slate-100 text-slate-700 text-xs px-2.5 py-1 rounded">Project</span></td>
                            <td class="px-6 py-4"><span
                                    class="text-emerald-600 bg-emerald-50 text-xs font-semibold px-2 py-0.5 rounded">Published</span>
                            </td>
                            <td class="px-6 py-4 text-xs">Aug 10, 2026</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button class="text-slate-400 hover:text-emerald-600 p-1"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button onclick="openDeleteModal('Free Education Material Distribution')"
                                    class="text-slate-400 hover:text-rose-600 p-1"><i
                                        class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                        <tr class="hover:bg-slate-50/50" id="row-3">
                            <td class="px-6 py-4 font-semibold text-slate-800">Role of Youth in Community Development
                            </td>
                            <td class="px-6 py-4"><span
                                    class="bg-slate-100 text-slate-700 text-xs px-2.5 py-1 rounded">Blog</span></td>
                            <td class="px-6 py-4"><span
                                    class="text-amber-600 bg-amber-50 text-xs font-semibold px-2 py-0.5 rounded">Draft</span>
                            </td>
                            <td class="px-6 py-4 text-xs">Aug 02, 2026</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button class="text-slate-400 hover:text-emerald-600 p-1"><i
                                        class="fa-solid fa-pen"></i></button>
                                <button onclick="openDeleteModal('Role of Youth in Community Development')"
                                    class="text-slate-400 hover:text-rose-600 p-1"><i
                                        class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Dashboard Footer inside main scroll area -->
    <footer class="bg-white border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-500 mt-auto">
        <p>&copy; 2026 bishwas.org - All Rights Reserved. Powered by Bishwas Foundation.</p>
    </footer>

</main>