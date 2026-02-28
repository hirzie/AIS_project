<?php
require_once '../../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struktur Organisasi - SekolahOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <link href="../../assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .org-tree * { box-sizing: border-box; }
        .org-tree { display: inline-block; min-width: 100%; padding: 20px; transform: scale(0.85); transform-origin: top center; }
        .org-tree ul { padding-top: 20px; position: relative; transition: all 0.5s; display: flex; justify-content: center; }
        .org-tree li { text-align: center; list-style-type: none; position: relative; padding: 20px 10px 0 10px; transition: all 0.5s; }
        .org-tree ul::before { content: ''; position: absolute; top: 0; left: 50%; border-left: 2px solid #cbd5e1; width: 0; height: 20px; transform: translateX(-50%); }
        .org-tree > ul::before { display: none; }
        .org-tree li::before, .org-tree li::after { content: ''; position: absolute; top: 0; right: 50%; border-top: 2px solid #cbd5e1; width: 50%; height: 20px; }
        .org-tree li::after { right: auto; left: 50%; border-left: 2px solid #cbd5e1; }
        .org-tree li:only-child::after, .org-tree li:only-child::before { display: none; }
        .org-tree li:only-child { padding-top: 0; }
        .org-tree li:only-child > .node-card::before { content: ''; position: absolute; top: -20px; left: 50%; border-left: 2px solid #cbd5e1; width: 0; height: 20px; }
        .org-tree li:first-child::before, .org-tree li:last-child::after { border: 0 none; }
        .org-tree li:first-child::after { border-radius: 5px 0 0 0; }
        .org-tree li:last-child::before { border-radius: 0 5px 0 0; border-right: 2px solid #cbd5e1; }
        .node-card { display: inline-block; background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; width: 260px; position: relative; z-index: 10; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.3s ease; text-align: left; }
        .node-card:hover { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); transform: translateY(-2px); border-color: #3b82f6; }
        .avatar-circle { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; font-size: 12px; flex-shrink: 0; }
    </style>
</head>
<body>
<div id="app" class="min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6 z-20 shadow-sm relative flex-none">
        <div class="flex items-center gap-3">
            <a href="dashboard.php" class="w-10 h-10 bg-slate-100 hover:bg-slate-200 rounded-lg flex items-center justify-center text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800 leading-none">Struktur Organisasi</h1>
                <span class="text-xs text-slate-500 font-medium">Visualisasi Hierarki Jabatan</span>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center bg-slate-100 rounded-lg p-1 border border-slate-200">
                <button @click="unitFilter = 'all'; subUnitFilter = ''; fetchSubUnits(); fetchData()" :class="unitFilter === 'all' ? 'bg-white shadow text-slate-800 font-bold' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 rounded-md text-xs transition-all">Semua</button>
                <button v-for="unit in units" :key="unit.id" @click="unitFilter = unit.id; subUnitFilter = ''; fetchSubUnits(); fetchData()" :class="unitFilter == unit.id ? 'bg-white shadow text-slate-800 font-bold' : 'text-slate-500 hover:text-slate-700'" class="px-3 py-1.5 rounded-md text-xs transition-all">{{ unit.prefix || unit.unit_level }}</button>
            </div>
            <button @click="openAddRootModal" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i> Tambah Root
            </button>
        </div>
    </header>
    <main class="flex-1 overflow-auto bg-slate-50 relative cursor-grab active:cursor-grabbing p-10 text-center">
        <div class="absolute top-4 right-4 z-20 flex items-center gap-2 bg-white/80 backdrop-blur-sm p-2 rounded-lg shadow-sm border border-slate-200">
            <label class="text-xs font-bold text-slate-500 uppercase">Filter Sub Unit:</label>
            <select v-model="subUnitFilter" @change="fetchData" class="text-sm bg-transparent border-none focus:ring-0 text-slate-700 font-medium cursor-pointer">
                <option value="">- Semua -</option>
                <option v-for="sub in subUnits" :key="sub" :value="sub">{{ sub }}</option>
            </select>
            <button v-if="subUnitFilter" @click="subUnitFilter = ''; fetchData()" class="text-slate-400 hover:text-red-500"><i class="fas fa-times"></i></button>
        </div>
        <div v-if="loading" class="mt-20 text-slate-400"><i class="fas fa-circle-notch fa-spin text-3xl"></i></div>
        <div v-else-if="treeData.length === 0" class="mt-20 text-slate-400"><i class="fas fa-sitemap text-4xl mb-3 text-slate-300"></i><p>Belum ada data struktur organisasi untuk filter ini.</p></div>
        <div v-else class="org-tree">
            <ul>
                <tree-item v-for="rootNode in treeData" :key="rootNode.id" :model="rootNode" @add="openAddModal" @duplicate="duplicateSlot" @add-sibling="openAddSiblingModal" @edit="openEditModal" @delete="openDeleteModal" @edit-slot="handleEditSlot" @delete-slot="handleDeleteSlot" :siblings-count="treeData.length"></tree-item>
            </ul>
        </div>
    </main>
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-opacity" @click.self="showModal = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all scale-100">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800">{{ isEdit ? 'Edit Jabatan' : 'Tambah Jabatan' }}</h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>
            <form @submit.prevent="savePosition" class="p-6 space-y-4">
                <div v-if="!isEdit && parentNode" class="bg-blue-50 text-blue-800 px-3 py-2 rounded-lg text-sm mb-4"><span class="font-bold">Atasan:</span> {{ parentNode.position_name }}</div>
                <div v-if="isEdit">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Atasan / Parent</label>
                    <select v-model="form.parent_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option :value="null">Root (Paling Atas)</option>
                        <option v-for="pos in availablePositions" :key="pos.id" :value="pos.id" :disabled="pos.id === form.id">{{ pos.position_name }} ({{ pos.prefix || 'Global' }})</option>
                    </select>
                    <p class="text-xs text-slate-400 mt-1">Ubah atasan untuk memindahkan posisi dalam bagan.</p>
                </div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">Nama Jabatan <span class="text-red-500">*</span></label><input type="text" v-model="form.position_name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" placeholder="Contoh: Wakil Kepala Sekolah"></div>
                <div><label class="block text-sm font-medium text-slate-700 mb-1">Sub Unit (Opsional)</label><input type="text" v-model="form.sub_unit" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Contoh: TKIT, SDIT (untuk filter)"></div>
                <div class="flex gap-3">
                    <div class="flex-1"><label class="block text-sm font-medium text-slate-700 mb-1">Urutan</label><input type="number" v-model="form.sort_order" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="0"><p class="text-[10px] text-slate-400 mt-1">Kiri (-) ke Kanan (+)</p></div>
                    <div class="flex-1"><label class="block text-sm font-medium text-slate-700 mb-1">Jarak Atas</label><input type="number" v-model="form.vertical_spacer" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="0"><p class="text-[10px] text-slate-400 mt-1">Jarak vertikal (px)</p></div>
                    <div class="flex-1"><label class="block text-sm font-medium text-slate-700 mb-1">Geser Samping</label><input type="number" v-model="form.horizontal_spacer" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="0"><p class="text-[10px] text-slate-400 mt-1">Geser +Garis (px)</p></div>
                </div>
                <div v-if="!isEdit">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Unit Utama (Core)</label>
                    <select v-model="form.unit_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option :value="null">Global / Yayasan</option>
                        <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.prefix || unit.unit_level }} ({{ unit.name }})</option>
                    </select>
                </div>
                <div v-if="!isEdit"><label class="block text-sm font-medium text-slate-700 mb-1">Sub Unit (Opsional)</label><input type="text" v-model="form.sub_unit" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500" placeholder="Contoh: Asrama, Dapur, Keamanan"><p class="text-xs text-slate-400 mt-1">Nama bagian khusus (jika ada).</p></div>
                <div class="pt-4 flex justify-end gap-3"><button type="button" @click="showModal = false" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-medium transition-colors">Batal</button><button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm disabled:opacity-50 flex items-center gap-2"><i v-if="saving" class="fas fa-circle-notch fa-spin"></i>{{ saving ? 'Menyimpan...' : 'Simpan' }}</button></div>
            </form>
        </div>
    </div>
</div>
<script>
const { createApp } = Vue
const TreeItem = { name:'tree-item', template: `
    <li>
        <div v-if="model.vertical_spacer" class="absolute left-1/2 border-l-2 border-slate-300 -translate-x-1/2" :style="{ top: '0px', height: (model.vertical_spacer + (siblingsCount > 1 ? 20 : 0)) + 'px' }"></div>
        <div v-if="model.horizontal_spacer" class="absolute border-t-2 border-slate-300" :style="{ top: (model.vertical_spacer || 0) + (siblingsCount > 1 ? 20 : 0) + 'px', left: '50%', width: Math.abs(model.horizontal_spacer) + 'px', transform: model.horizontal_spacer > 0 ? 'translateX(0)' : 'translateX(-100%)' }"></div>
        <div v-if="model.horizontal_spacer" class="absolute border-l-2 border-slate-300" :style="{ top: (model.vertical_spacer || 0) + (siblingsCount > 1 ? 20 : 0) + 'px', left: '50%', height: '10px', transform: 'translateX(' + (model.horizontal_spacer - 1) + 'px)' }"></div>
        <div class="node-card group" :style="{ marginTop: (model.vertical_spacer || 0) + 'px', transform: model.horizontal_spacer ? 'translateX(' + model.horizontal_spacer + 'px)' : 'none' }">
            <div class="mb-3 border-b border-slate-100 pb-2 relative">
                <div class="font-bold text-slate-800 text-[13px] leading-tight pr-6">
                    {{ model.position_name }}
                    <span v-if="model.sub_unit" class="ml-1 inline-block bg-slate-100 text-slate-500 text-[9px] px-1.5 py-0.5 rounded border border-slate-200 align-middle font-normal">{{ model.sub_unit }}</span>
                </div>
                <div class="absolute -top-1 -right-1 flex flex-col gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity z-20 bg-white/90 backdrop-blur-sm rounded-md shadow-sm border border-slate-100 p-0.5">
                    <button @click.stop="$emit('add', model)" class="w-5 h-5 rounded bg-transparent hover:bg-blue-50 text-slate-400 hover:text-blue-600 flex items-center justify-center transition-colors" title="Tambah Bawahan"><i class="fas fa-plus text-[9px]"></i></button>
                    <button @click.stop="$emit('add-sibling', model)" class="w-5 h-5 rounded bg-transparent hover:bg-green-50 text-slate-400 hover:text-green-600 flex items-center justify-center transition-colors" title="Tambah Samping (Sibling)"><i class="fas fa-arrow-right text-[9px]"></i></button>
                    <button @click.stop="$emit('edit', model)" class="w-5 h-5 rounded bg-transparent hover:bg-orange-50 text-slate-400 hover:text-orange-600 flex items-center justify-center transition-colors" title="Edit Jabatan"><i class="fas fa-pencil-alt text-[9px]"></i></button>
                    <button @click.stop="$emit('delete', model)" class="w-5 h-5 rounded bg-transparent hover:bg-red-50 text-slate-400 hover:text-red-600 flex items-center justify-center transition-colors" title="Hapus Jabatan"><i class="fas fa-trash text-[9px]"></i></button>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="avatar-circle" :style="{ background: model.avatar_color || '#64748b' }">{{ getInitials(model.official_name) }}</div>
                <div class="flex-1">
                    <div class="text-[12px] font-bold text-slate-700">{{ model.official_name || '-' }}</div>
                    <div class="text-[10px] text-slate-500">{{ model.official_identity || '-' }}</div>
                </div>
            </div>
            <div class="mt-2 grid grid-cols-3 gap-2">
                <button @click.stop="$emit('add', model)" class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-[10px] hover:bg-blue-100 transition-colors"><i class="fas fa-plus mr-1"></i> Bawahan</button>
                <button @click.stop="$emit('add-sibling', model)" class="px-2 py-1 bg-green-50 text-green-600 rounded text-[10px] hover:bg-green-100 transition-colors"><i class="fas fa-arrow-right mr-1"></i> Samping</button>
                <button @click.stop="$emit('edit', model)" class="px-2 py-1 bg-orange-50 text-orange-600 rounded text-[10px] hover:bg-orange-100 transition-colors"><i class="fas fa-pencil-alt mr-1"></i> Edit</button>
            </div>
            <div class="mt-3 text-[10px] text-slate-400">Urutan: {{ model.sort_order || 0 }} • Jarak: {{ model.vertical_spacer || 0 }}px • Geser: {{ model.horizontal_spacer || 0 }}px</div>
            <div v-if="model.children && model.children.length" class="mt-4">
                <ul><tree-item v-for="child in model.children" :key="child.id" :model="child" @add="$emit('add', $event)" @add-sibling="$emit('add-sibling', $event)" @edit="$emit('edit', $event)" @delete="$emit('delete', $event)" @edit-slot="$emit('edit-slot', $event)" @delete-slot="$emit('delete-slot', $event)" :siblings-count="model.children.length"></tree-item></ul>
            </div>
        </div>
    </li>
`, props:['model','siblingsCount'], emits:['add','add-sibling','edit','delete','edit-slot','delete-slot'], methods:{ getInitials(name){ if(!name) return '?'; const parts=name.trim().split(' '); return (parts[0][0]||'').toUpperCase() + (parts.length>1 ? (parts[parts.length-1][0]||'').toUpperCase() : ''); } } }
createApp({
    components:{ 'tree-item': TreeItem },
    data(){ return { loading:false, saving:false, units:[], subUnits:[], unitFilter:'all', subUnitFilter:'', treeData:[], showModal:false, isEdit:false, parentNode:null, availablePositions:[], editingSlotId:null, form:{ id:null, position_name:'', parent_id:null, unit_id:null, sub_unit:'', sort_order:0, vertical_spacer:0, horizontal_spacer:0 } } },
    methods:{
        getBaseUrl(){ let baseUrl=window.BASE_URL||'/'; if(baseUrl==='/'||!baseUrl){ const m=(window.location.pathname||'').match(/^\/(AIS|AIStest)\//i); baseUrl = m ? `/${m[1]}/` : '/'; } return baseUrl; },
        async fetchUnits(){ try{ const res=await fetch(this.getBaseUrl() + 'api/get_units.php'); const data=await res.json(); this.units=data||[]; } catch(e){ this.units=[]; } },
        async fetchSubUnits(){ try{ const res=await fetch(this.getBaseUrl() + 'api/hr_positions.php?action=list_sub_units'+(this.unitFilter!=='all' ? ('&unit_id='+this.unitFilter):'')); const data=await res.json(); this.subUnits=data.data||[]; } catch(e){ this.subUnits=[]; } },
        async fetchData(){ this.loading=true; try{ let url=this.getBaseUrl() + 'api/hr_positions.php?action=get_tree'; const params=[]; if(this.unitFilter!=='all') params.push('unit_id='+this.unitFilter); if(this.subUnitFilter) params.push('sub_unit='+encodeURIComponent(this.subUnitFilter)); if(params.length>0) url += '&'+params.join('&'); const res=await fetch(url); const data=await res.json(); this.treeData=(data.data||[]).sort((a,b)=>Number(a.sort_order)-Number(b.sort_order)); } catch(e){ this.treeData=[]; } finally{ this.loading=false; } },
        openAddRootModal(){ this.isEdit=false; this.parentNode=null; let defaultUnit=null; if(this.unitFilter!=='all') defaultUnit=this.unitFilter; this.editingSlotId=null; this.form={ id:null, position_name:'', parent_id:null, unit_id:defaultUnit, sub_unit:'', sort_order:0, vertical_spacer:0, horizontal_spacer:0 }; this.showModal=true; },
        openAddModal(parent){ this.isEdit=false; this.parentNode=parent; this.editingSlotId=null; this.form={ id:null, position_name:'', parent_id:parent.id, unit_id:parent.unit_id||null, sub_unit:parent.sub_unit||'', sort_order:0, vertical_spacer:0, horizontal_spacer:0 }; this.showModal=true; },
        openAddSiblingModal(node){ this.isEdit=false; this.parentNode=null; this.editingSlotId=null; this.form={ id:null, position_name:'', parent_id:node.parent_id||null, unit_id:node.unit_id||null, sub_unit:node.sub_unit||'', sort_order:(Number(node.sort_order)||0)+1, vertical_spacer:0, horizontal_spacer:0 }; this.showModal=true; },
        async openEditModal(node){ this.isEdit=true; this.parentNode=null; this.editingSlotId=null; this.form={ id:node.id, position_name:node.position_name, parent_id:node.parent_id, unit_id:node.unit_id||null, sub_unit:node.sub_unit||'', sort_order:Number(node.sort_order)||0, vertical_spacer:Number(node.vertical_spacer)||0, horizontal_spacer:Number(node.horizontal_spacer)||0 }; try{ const res=await fetch(this.getBaseUrl() + 'api/hr_positions.php?action=list_positions'); const data=await res.json(); this.availablePositions=data.data||[]; } catch(e){ this.availablePositions=[]; } this.showModal=true; },
        async savePosition(){ this.saving=true; try{ const payload={ ...this.form }; const method=this.isEdit ? 'update' : 'create'; const res=await fetch(this.getBaseUrl() + 'api/hr_positions.php?action='+method, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) }); const data=await res.json(); if(data.success){ this.showModal=false; this.fetchData(); } else { alert('Gagal menyimpan: '+(data.message||'unknown')); } } catch(e){ alert('Terjadi kesalahan jaringan'); } finally{ this.saving=false; } },
        duplicateSlot(node){ this.isEdit=false; const payload={ ...node, id:null, position_name: node.position_name+' (Copy)', sort_order: (Number(node.sort_order)||0)+1 }; fetch(this.getBaseUrl() + 'api/hr_positions.php?action=create', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) }).then(res=>res.json()).then(d=>{ if(d.success){ this.fetchData(); } }); },
        async handleEditSlot(node){ this.openEditModal(node); this.editingSlotId=node.id; },
        async handleDeleteSlot(node){ this.deleteTarget=node; this.showDeleteModal=true; },
        async openDeleteModal(node){ if(node.children && node.children.length>0){ alert('Tidak dapat menghapus jabatan karena memiliki bawahan. Hapus/migrasikan bawahan terlebih dahulu.'); return; } this.deleteTarget=node; this.showDeleteModal=true; },
        async performDelete(){ try{ const res=await fetch(this.getBaseUrl() + 'api/hr_positions.php?action=delete', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ id:this.deleteTarget.id }) }); const data=await res.json(); if(data.success){ this.showDeleteModal=false; this.fetchData(); } else { alert('Gagal menghapus: '+(data.message||'')); } } catch(e){ alert('Kesalahan jaringan'); } }
    },
    mounted(){ this.fetchUnits(); this.fetchSubUnits(); this.fetchData(); }
}).mount('#app')
</script>
</body>
</html>
