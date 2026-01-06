@extends('layouts.app')

@section('content')
<div x-data="templateManager()" x-init="loadTemplates()">
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Document Templates</h1>
                <p class="mt-2 text-sm text-gray-600">Manage receipt, invoice, and delivery note templates</p>
            </div>
            <button type="button" @click.prevent="showAddModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Add Template
            </button>
        </div>

        <!-- Templates Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Default</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="template in templates" :key="template.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="template.template_type"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="template.template_name"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span 
                                    x-show="template.is_default"
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"
                                >
                                    Default
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(template.created_at)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="editTemplate(template)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</button>
                                <button @click="deleteTemplate(template)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

    <!-- Add/Edit Modal -->
    <div x-show="showAddModal || showEditModal" class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form @submit.prevent="saveTemplate()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" x-text="showAddModal ? 'Add Template' : 'Edit Template'"></h3>
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Template Type *</label>
                                    <select x-model="form.template_type" required class="mt-1 block w-full rounded-md border-gray-300">
                                        <option value="receipt">Receipt</option>
                                        <option value="invoice">Invoice</option>
                                        <option value="delivery_note">Delivery Note</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Template Name *</label>
                                    <input type="text" x-model="form.template_name" required class="mt-1 block w-full rounded-md border-gray-300">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Template Data (JSON) *</label>
                                <textarea 
                                    x-model="templateDataJson" 
                                    rows="10" 
                                    required 
                                    class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm"
                                    placeholder='{"header": "Hospital Name", "footer": "Thank you!"}'
                                ></textarea>
                                <p class="mt-1 text-xs text-gray-500">Enter valid JSON format</p>
                            </div>
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="form.is_default" class="rounded border-gray-300 text-indigo-600">
                                    <span class="ml-2 text-sm text-gray-700">Set as default template</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
                        </button>
                        <button type="button" @click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.templateManager = function() {
    window.templateManager = templateManager;
    return {
        templates: [],
        showAddModal: false,
        showEditModal: false,
        templateDataJson: '{}',
        form: {
            id: null,
            template_type: 'receipt',
            template_name: '',
            template_data: {},
            is_default: false
        },

        async loadTemplates() {
            try {
                const response = await axios.get('/document-templates');
                this.templates = response.data;
            } catch (error) {
                console.error('Error loading templates:', error);
            }
        },

        editTemplate(template) {
            this.form = {
                id: template.id,
                template_type: template.template_type,
                template_name: template.template_name,
                template_data: template.template_data || {},
                is_default: template.is_default || false
            };
            this.templateDataJson = JSON.stringify(template.template_data || {}, null, 2);
            this.showEditModal = true;
        },

        async saveTemplate() {
            try {
                // Parse JSON
                this.form.template_data = JSON.parse(this.templateDataJson);
            } catch (error) {
                alert('Invalid JSON format. Please check your template data.');
                return;
            }

            try {
                if (this.form.id) {
                    await axios.put(`/document-templates/${this.form.id}`, this.form);
                } else {
                    await axios.post('/document-templates', this.form);
                }
                this.closeModal();
                this.loadTemplates();
                alert('Template saved successfully');
            } catch (error) {
                alert('Error saving template: ' + (error.response?.data?.message || error.message));
            }
        },

        async deleteTemplate(template) {
            if (!confirm(`Are you sure you want to delete ${template.template_name}?`)) return;
            try {
                await axios.delete(`/document-templates/${template.id}`);
                this.loadTemplates();
                alert('Template deleted successfully');
            } catch (error) {
                alert('Error deleting template: ' + (error.response?.data?.message || error.message));
            }
        },

        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.form = {
                id: null,
                template_type: 'receipt',
                template_name: '',
                template_data: {},
                is_default: false
            };
            this.templateDataJson = '{}';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('en-KE');
        }
    }
}
</script>
@endsection

