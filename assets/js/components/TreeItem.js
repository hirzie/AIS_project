export const TreeItem = {
    name: 'tree-item', // Penting untuk recursive self-call
    template: `
        <li>
            <div class="org-node group relative min-w-[180px]">
                <div class="font-bold text-slate-800 text-sm mb-1 pb-1 border-b border-slate-100">{{ model.position_name }}</div>
                
                <!-- CASE 1: BANYAK PEJABAT (GROUP) -->
                <div v-if="model.officials && model.officials.length > 1" class="text-left max-h-[120px] overflow-y-auto pr-1 custom-scrollbar">
                    <div v-for="(off, idx) in model.officials" :key="idx" class="mb-2 pb-1 border-b border-dashed border-slate-100 last:border-0">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-bold text-slate-400 w-4 text-center">{{ idx + 1 }}.</span>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-slate-700 truncate" :title="off.official_name">{{ off.official_name }}</div>
                                <div class="text-[9px] text-blue-500 truncate">{{ off.sk_number }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CASE 2: SINGLE PEJABAT -->
                <div v-else-if="model.official_name" class="mt-2">
                    <div class="flex items-center justify-center gap-2 mb-1">
                        <img :src="'https://ui-avatars.com/api/?name=' + model.official_name + '&background=random&size=32'" class="w-8 h-8 rounded-full border border-white shadow-sm">
                        <div class="text-left">
                            <div class="text-xs text-slate-700 font-bold leading-tight">{{ model.official_name }}</div>
                            <div class="text-[9px] text-slate-400">{{ model.employee_number }}</div>
                        </div>
                    </div>
                    <div v-if="model.sk_number" class="text-[9px] text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full inline-block mt-1">
                        SK: {{ model.sk_number }}
                    </div>
                </div>

                <!-- CASE 3: KOSONG -->
                <div v-else class="py-2 text-xs text-slate-400 italic">
                    - Vacant -
                </div>

                <!-- Edit Button -->
                <button 
                    @click.stop="$emit('edit-node', model)" 
                    class="absolute -top-2 -right-2 w-6 h-6 bg-white border border-slate-200 text-slate-500 rounded-full flex items-center justify-center text-xs hover:text-blue-600 hover:border-blue-500 shadow-sm z-20"
                    title="Edit Jabatan & Pejabat">
                    <i class="fas fa-pencil-alt"></i>
                </button>

                <!-- Add Child Button -->
                <button 
                    @click.stop="$emit('add-node', model)" 
                    class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity shadow-md z-10"
                    title="Tambah Bawahan">
                    <i class="fas fa-plus"></i>
                </button>
            </div>

            <!-- STANDARD TREE MODE (If children <= 8) -->
            <ul v-if="model.children && model.children.length > 0 && model.children.length <= 8">
                <tree-item 
                    v-for="child in model.children" 
                    :key="child.id" 
                    :model="child" 
                    @add-node="$emit('add-node', $event)"
                    @edit-node="$emit('edit-node', $event)"
                ></tree-item>
            </ul>

            <!-- GRID MODE (If children > 8) -->
            <div v-if="model.children && model.children.length > 8" class="org-grid-wrapper">
                <div class="org-grid-container">
                    <div v-for="child in model.children" :key="child.id" class="org-node-compact group relative hover:shadow-md transition-shadow cursor-pointer" @click.stop="$emit('edit-node', child)">
                        <img :src="'https://ui-avatars.com/api/?name=' + (child.official_name || 'X') + '&background=random&size=32'" class="w-8 h-8 rounded-full flex-shrink-0">
                        <div class="overflow-hidden">
                            <div class="font-bold text-slate-700 truncate" :title="child.position_name">{{ child.position_name }}</div>
                            <div class="text-slate-500 truncate" :title="child.official_name">{{ child.official_name || 'Kosong' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </li>
    `,
    props: {
        model: Object
    },
    emits: ['add-node', 'edit-node']
};
