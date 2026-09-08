<!-- PAGE 1: DASHBOARD OVERVIEW PAGE -->
<?php include 'dashboard.php'; ?>

<!-- PAGE 2: BENEFICIARIES PAGE -->
<section id="page-beneficiaries" class="page-content hidden space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Service Beneficiaries</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage individuals and families receiving support.</p>
        </div>
        <button onclick="openAddModal('Beneficiary')"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Add Beneficiary
        </button>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
        <p class="text-xs text-slate-500">Beneficiary Management Directory loaded.</p>
    </div>
</section>

<!-- PAGE 3: VOLUNTEERS PAGE -->
<section id="page-volunteers" class="page-content hidden space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Volunteers Roster</h2>
            <p class="text-xs text-slate-500 mt-0.5">Active volunteer management and task assignments.</p>
        </div>
        <button onclick="openAddModal('Volunteer')"
            class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-teal-600/20 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Register Volunteer
        </button>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
        <p class="text-xs text-slate-500">Volunteers team records and deployment details.</p>
    </div>
</section>

<!-- PAGE 4: MEMBERS & DONORS PAGE -->
<section id="page-members" class="page-content hidden space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Members & Donors</h2>
            <p class="text-xs text-slate-500 mt-0.5">Foundation sponsors, recurring donors and members.</p>
        </div>
        <button onclick="openAddModal('Member')"
            class="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-sky-600/20 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Add New Donor
        </button>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
        <p class="text-xs text-slate-500">Donors directory and subscription history.</p>
    </div>
</section>

<!-- PAGE 5: PROJECTS PAGE -->
<section id="page-projects" class="page-content hidden space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Projects & Sector Funds</h2>
            <p class="text-xs text-slate-500 mt-0.5">Orphanage support, Tree planting, Emergency relief.</p>
        </div>
        <button onclick="openAddModal('Project')"
            class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-amber-600/20 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Create New Project
        </button>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
        <p class="text-xs text-slate-500">Active campaigns and allocated budget track.</p>
    </div>
</section>

<!-- PAGE 6: REPORTS PAGE -->
<section id="page-reports" class="page-content hidden space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Financial Reports & Audit</h2>
            <p class="text-xs text-slate-500 mt-0.5">Generate transparent donation statements and operational audits.
            </p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
        <p class="text-xs text-slate-500">Exportable CSV & PDF financial statements.</p>
    </div>
</section>