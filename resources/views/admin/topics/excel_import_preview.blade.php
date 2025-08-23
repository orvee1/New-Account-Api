{{-- Client-side Excel/CSV Preview Modal --}}
<div id="excelPreviewModal"
    class="hidden fixed inset-0 bg-black/50 items-start justify-center z-50 p-4 sm:p-8 overflow-y-auto">
    <div class="bg-white w-full max-w-5xl rounded-xl shadow-xl ring-1 ring-gray-200">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Import Preview</h3>
            <button type="button" onclick="closeExcelPreview()"
                class="px-3 py-1 rounded bg-red-600 text-white">Close</button>
        </div>

        <div class="px-5 py-3 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="rounded-lg bg-gray-50 p-3">
                <div class="text-sm text-gray-600">Total rows</div>
                <div id="sumTotal" class="text-xl font-bold">0</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
                <div class="text-sm text-gray-600">Error rows</div>
                <div id="sumError" class="text-xl font-bold text-red-600">0</div>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
                <div class="text-sm text-gray-600">Can import</div>
                <div id="sumCanImport" class="text-xl font-bold text-emerald-700">0</div>
            </div>
        </div>

        <div class="px-5 pb-2 text-sm text-gray-600">
            Required headers: <code class="px-1 py-0.5 bg-gray-100 rounded">institute_id</code>,
            <code class="px-1 py-0.5 bg-gray-100 rounded">name</code>
            <span id="headerErrorMsg" class="ml-2 hidden text-red-600 font-medium">Missing required header(s)</span>
        </div>

        <div class="px-5 pb-4 overflow-x-auto max-h-[60vh]">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Row</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Institute ID</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Errors</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Can Import</th>
                    </tr>
                </thead>
                <tbody id="previewTbody" class="bg-white divide-y divide-gray-100"></tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-gray-200 flex items-center justify-end gap-2">
            <button type="button" onclick="closeExcelPreview()"
                class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">
                Cancel
            </button>
            <button id="confirmUploadBtn" type="button" onclick="submitExcelForm()"
                class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60 disabled:cursor-not-allowed">
                Confirm & Upload
            </button>
        </div>
    </div>
</div>
