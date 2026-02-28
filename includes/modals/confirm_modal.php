<!-- CONFIRMATION MODAL (SHARED) -->
<div v-if="confirmModal && confirmModal.show" v-cloak style="display: none;" :style="{ display: (confirmModal && confirmModal.show) ? 'flex' : 'none' }" class="fixed inset-0 z-[60] items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm overflow-hidden animate-fade transform transition-all scale-100">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800 mb-2">{{ confirmModal.title }}</h3>
            <p class="text-sm text-slate-500" v-html="confirmModal.message"></p>
        </div>
        <div class="p-4 border-t border-slate-100 bg-slate-50 flex gap-3">
            <button @click="confirmModal.show = false" class="flex-1 px-4 py-2 text-slate-600 hover:bg-slate-200 rounded-lg text-sm font-medium transition-colors">Batal</button>
            <button @click="executeConfirm" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors shadow-sm">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>