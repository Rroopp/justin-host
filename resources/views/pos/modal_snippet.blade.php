    <!-- Sale Success Modal -->
    <div x-show="showSuccessModal" x-transition.opacity class="fixed z-50 inset-0 overflow-y-auto" style="display: none;" x-cloak>
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>

            <div class="relative inline-block align-middle bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-sm sm:w-full p-6">
                
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4 animate-[bounce_1s_ease-in-out_1]">
                    <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                
                <div class="text-center">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Sale Successful!</h3>
                    <p class="text-sm text-gray-500 mb-6">
                        Transaction recorded successfully.
                    </p>
                    
                    <div class="flex flex-col gap-3">
                        <button 
                            @click="printDocument('receipt')"
                            class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2-4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            Print Receipt
                        </button>
                        
                        <button 
                            @click="closeSuccessModal()"
                            class="w-full inline-flex justify-center items-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-bold rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                        >
                            Start New Sale
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
