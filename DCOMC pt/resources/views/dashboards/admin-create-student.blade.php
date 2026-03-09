<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Student Account - DCOMC</title>
    @include('layouts.partials.offline-assets')
    @include('dashboards.partials.dcomc-redesign-styles')
</head>
<body class="dashboard-wrap bg-[#F1F5F9] flex min-h-screen text-gray-800 font-data" x-data="adminCreateStudentApp()">

    @include('dashboards.partials.admin-sidebar')
    @include('dashboards.partials.admin-loading-bar')

    <main class="dashboard-main flex-1 min-w-0 overflow-y-auto p-6 md:p-8">
            <div class="max-w-6xl mx-auto">
                <section class="hero-gradient rounded-2xl shadow-xl px-6 py-5 sm:px-8 sm:py-6 text-white mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="font-heading text-2xl sm:text-3xl font-bold tracking-tight">Create Student Account</h1>
                            <p class="text-white/90 text-sm sm:text-base mt-1.5 font-data">Same as Manual Registration. Fill required fields; email can be left blank to auto-generate. Block is assigned after creation.</p>
                        </div>
                        <a href="{{ route('admin.accounts') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold bg-white/20 hover:bg-white/30 text-white no-underline transition-colors font-data shrink-0">← Back to Accounts</a>
                    </div>
                </section>

                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden flex flex-col border-t-[10px] border-t-[#1E40AF]">
                    <div class="px-6 py-3 bg-[#EFF6FF] border-b border-[#BFDBFE] text-sm text-[#1E40AF] font-data">
                        <strong class="font-heading">Tip:</strong> Complete all required sections. Use the tabs to move between Basic Info, Address, Family & Income, Education, and Administrative.
                    </div>

                    <form method="POST" action="{{ route('admin.accounts.store.student') }}" class="flex flex-col">
                        @csrf
                        @if($errors->any())
                            <div class="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                                <p class="font-semibold mb-1">Please fix the following:</p>
                                <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                            </div>
                        @endif
                        <div class="flex flex-col lg:flex-row">
                            <div class="w-full lg:w-80 xl:w-96 bg-[#F8FAFC] border-b lg:border-b-0 lg:border-r border-gray-200 p-6 shrink-0">
                                <div class="flex items-center mb-6 text-gray-800 font-heading font-semibold border-b border-gray-200 pb-3">
                                    <span class="w-8 h-8 rounded-lg bg-[#1E40AF]/10 flex items-center justify-center text-[#1E40AF] mr-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </span>
                                    New Student
                                </div>
                                <div class="flex flex-col items-center mb-6">
                                    <div class="w-20 h-20 bg-white rounded-xl flex items-center justify-center text-[#1E40AF]/40 border border-gray-200 shadow-sm">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                    </div>
                                    <span class="text-xs font-heading font-bold text-gray-500 tracking-wider mt-2">Enrollment details</span>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block font-heading text-xs font-bold text-gray-600 mb-1.5 uppercase tracking-wide">Program</label>
                                        <select name="course" required x-model="selectedProgram" @change="syncMajorsByProgram()" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white">
                                            <option value="">— Select Program —</option>
                                            @foreach($programOptions as $prog)
                                                <option value="{{ $prog }}" {{ old('course') === $prog ? 'selected' : '' }}>{{ $prog }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-heading text-xs font-bold text-gray-600 mb-1.5 uppercase tracking-wide">Major</label>
                                        <select name="major" x-model="selectedMajor" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white" :disabled="availableMajors.length === 0">
                                            <option value="">— Major (optional) —</option>
                                            <template x-for="major in availableMajors" :key="major">
                                                <option :value="major" x-text="major"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-heading text-xs font-bold text-gray-600 mb-1.5 uppercase tracking-wide">Year Level</label>
                                        <select name="year_level" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white">
                                            @forelse($yearLevels as $yearLevel)
                                                <option value="{{ $yearLevel }}" {{ old('year_level') === $yearLevel ? 'selected' : '' }}>{{ $yearLevel }}</option>
                                            @empty
                                                <option>No year level saved</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-heading text-xs font-bold text-gray-600 mb-1.5 uppercase tracking-wide">Semester</label>
                                        <select name="semester" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white">
                                            @forelse($semesters as $semester)
                                                <option value="{{ $semester }}" {{ old('semester') === $semester ? 'selected' : '' }}>{{ $semester }}</option>
                                            @empty
                                                <option>No semester saved</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-heading text-xs font-bold text-gray-600 mb-1.5 uppercase tracking-wide">School Year</label>
                                        <select name="school_year" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white">
                                            @forelse($schoolYears ?? [] as $sy)
                                                <option value="{{ $sy }}" {{ old('school_year', $defaultSchoolYear ?? $schoolYears->first() ?? '') === $sy ? 'selected' : '' }}>{{ $sy }}</option>
                                            @empty
                                                @php $current = now()->month >= 6 ? now()->year . '-' . (now()->year + 1) : (now()->year - 1) . '-' . now()->year; @endphp
                                                <option value="{{ $current }}" selected>{{ $current }}</option>
                                            @endforelse
                                        </select>
                                        <p class="text-xs text-gray-500 mt-0.5 font-data">Defaults to current</p>
                                    </div>
                                    <div>
                                        <label class="block font-heading text-xs font-bold text-gray-600 mb-1.5 uppercase tracking-wide">Shift</label>
                                        <select name="shift" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white">
                                            <option value="day" {{ old('shift', 'day') === 'day' ? 'selected' : '' }}>Day</option>
                                            <option value="night" {{ old('shift') === 'night' ? 'selected' : '' }}>Night</option>
                                        </select>
                                    </div>
                                    <p class="text-xs text-gray-500 font-data">Block assigned after creation.</p>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0 flex flex-col bg-white">
                                <div class="flex border-b border-gray-200 px-8 pt-4 gap-6 shrink-0">
                                    <button type="button" @click="currentTab = 'basic'" :class="currentTab === 'basic' ? 'text-[#1E40AF] border-b-2 border-[#1E40AF] font-semibold' : 'text-gray-500 hover:text-[#1E40AF]'" class="pb-3 font-heading text-sm transition focus:outline-none border-b-2 border-transparent">Basic Info</button>
                                    <button type="button" @click="currentTab = 'address'" :class="currentTab === 'address' ? 'text-[#1E40AF] border-b-2 border-[#1E40AF] font-semibold' : 'text-gray-500 hover:text-[#1E40AF]'" class="pb-3 font-heading text-sm transition focus:outline-none border-b-2 border-transparent">Address</button>
                                    <button type="button" @click="currentTab = 'guardian'" :class="currentTab === 'guardian' ? 'text-[#1E40AF] border-b-2 border-[#1E40AF] font-semibold' : 'text-gray-500 hover:text-[#1E40AF]'" class="pb-3 font-heading text-sm transition focus:outline-none border-b-2 border-transparent">Family & Income</button>
                                    <button type="button" @click="currentTab = 'education'" :class="currentTab === 'education' ? 'text-[#1E40AF] border-b-2 border-[#1E40AF] font-semibold' : 'text-gray-500 hover:text-[#1E40AF]'" class="pb-3 font-heading text-sm transition focus:outline-none border-b-2 border-transparent">Education</button>
                                    <button type="button" @click="currentTab = 'admin'" :class="currentTab === 'admin' ? 'text-[#1E40AF] border-b-2 border-[#1E40AF] font-semibold' : 'text-gray-500 hover:text-[#1E40AF]'" class="pb-3 font-heading text-sm transition focus:outline-none border-b-2 border-transparent">Administrative</button>
                                </div>

                                <div class="flex-1 p-10 overflow-y-auto relative">
                                    <div x-show="currentTab === 'basic'" class="space-y-10">
                                        <section>
                                            <h4 class="font-heading text-center text-gray-600 text-sm font-semibold mb-8">Personal Details</h4>
                                            <div class="space-y-6">
                                                <div class="flex items-center"><label class="w-40 text-xs font-bold text-gray-700">First Name</label><input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white flex-1"></div>
                                                <div class="flex items-center"><label class="w-40 text-xs font-bold text-gray-700">Middle Name</label><input type="text" name="middle_name" value="{{ old('middle_name') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white flex-1"></div>
                                                <div class="flex items-center"><label class="w-40 text-xs font-bold text-gray-700">Last Name</label><input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white flex-1"></div>
                                            </div>
                                        </section>
                                        <section>
                                            <h4 class="font-heading text-center text-gray-600 text-sm font-semibold mb-8">Other Info</h4>
                                            <div class="grid grid-cols-2 gap-x-12 gap-y-6">
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Gender</label>
                                                    <select name="gender" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"><option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option><option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option></select>
                                                </div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Civil Status</label>
                                                    <select name="civil_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"><option value="Single" {{ old('civil_status', 'Single') === 'Single' ? 'selected' : '' }}>Single</option><option value="Married">Married</option><option value="Widowed">Widowed</option></select>
                                                </div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Citizenship</label><input type="text" name="citizenship" value="{{ old('citizenship', 'Filipino') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Contact No.</label><input type="text" name="phone" value="{{ old('phone') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white" placeholder="09xxxxxxxxx"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Email Address (optional)</label><input type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white" placeholder="Leave blank to auto-generate"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Date of Birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Place of Birth</label><input type="text" name="place_of_birth" value="{{ old('place_of_birth') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                            </div>
                                        </section>
                                    </div>

                                    <div x-show="currentTab === 'address'" class="space-y-10" style="display:none;">
                                        <section>
                                            <h4 class="font-heading text-center text-gray-600 text-sm font-semibold mb-8">Current Address</h4>
                                            <div class="grid grid-cols-2 gap-x-12 gap-y-6">
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">House No.</label><input type="text" name="house_number" value="{{ old('house_number') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Street / Subdivision</label><input type="text" name="street" value="{{ old('street') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Purok / Zone</label><input type="text" name="purok_zone" value="{{ old('purok_zone') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Barangay</label><input type="text" name="barangay" value="{{ old('barangay') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">City / Municipality</label><input type="text" name="municipality" value="{{ old('municipality') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Province</label><input type="text" name="province" value="{{ old('province') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Zip Code</label><input type="text" name="zip_code" value="{{ old('zip_code') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                            </div>
                                        </section>
                                    </div>

                                    <div x-show="currentTab === 'guardian'" class="space-y-10" style="display:none;">
                                        <section>
                                            <h4 class="text-center text-gray-400 text-sm mb-8">Family & Household</h4>
                                            <div class="grid grid-cols-2 gap-x-12 gap-y-6">
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Father's Full Name</label><input type="text" name="father_name" value="{{ old('father_name') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Occupation (Father)</label><input type="text" name="father_occupation" value="{{ old('father_occupation') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Mother's Maiden Name</label><input type="text" name="mother_name" value="{{ old('mother_name') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Occupation (Mother)</label><input type="text" name="mother_occupation" value="{{ old('mother_occupation') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Household's Monthly Income</label><input type="text" name="monthly_income" value="{{ old('monthly_income') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white" placeholder="e.g. 15000 or N/A"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">DSWD Household No.</label><input type="text" name="dswd_household_no" value="{{ old('dswd_household_no') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white" placeholder="N/A if not applicable"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">No. of Family Members</label><input type="number" name="num_family_members" value="{{ old('num_family_members') }}" min="0" max="99" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white" placeholder="N/A or number"></div>
                                                <div class="col-span-2 border-t border-gray-200 pt-6 mt-2"><label class="block font-heading text-xs font-bold text-[#1E40AF] mb-1 uppercase">Emergency Contact</label></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Contact Name</label><input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Contact Number</label><input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                            </div>
                                        </section>
                                    </div>

                                    <div x-show="currentTab === 'education'" class="space-y-10" style="display:none;">
                                        <section>
                                            <h4 class="font-heading text-center text-gray-600 text-sm font-semibold mb-8">Academic History</h4>
                                            <div class="space-y-8">
                                                <div class="grid grid-cols-3 gap-6">
                                                    <div class="col-span-2"><label class="block text-xs font-bold text-gray-700 mb-1">Last School Attended</label><input type="text" name="high_school" value="{{ old('high_school') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                    <div><label class="block text-xs font-bold text-gray-700 mb-1">Year Graduated</label><input type="text" name="hs_graduation_date" value="{{ old('hs_graduation_date') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                </div>
                                                <div class="grid grid-cols-2 gap-6">
                                                    <div><label class="block text-xs font-bold text-gray-700 mb-1">LRN (Learner Reference Number)</label><input type="text" name="lrn" value="{{ old('lrn') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white"></div>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-700 mb-1">Student Type</label>
                                                    <div class="flex flex-wrap gap-x-6 gap-y-1 mt-2">
                                                        <label class="flex items-center text-xs cursor-pointer"><input type="radio" name="student_type" value="Regular" {{ old('student_type', 'Regular') === 'Regular' ? 'checked' : '' }} class="mr-2"> Regular</label>
                                                        <label class="flex items-center text-xs cursor-pointer"><input type="radio" name="student_type" value="Transferee" {{ old('student_type') === 'Transferee' ? 'checked' : '' }} class="mr-2"> Transferee</label>
                                                        <label class="flex items-center text-xs cursor-pointer"><input type="radio" name="student_type" value="Returnee" {{ old('student_type') === 'Returnee' ? 'checked' : '' }} class="mr-2"> Returnee</label>
                                                        <label class="flex items-center text-xs cursor-pointer"><input type="radio" name="student_type" value="Irregular" {{ old('student_type') === 'Irregular' ? 'checked' : '' }} class="mr-2"> Irregular</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </section>
                                    </div>

                                    <div x-show="currentTab === 'admin'" class="space-y-10" style="display:none;">
                                        <section>
                                            <h4 class="font-heading text-center text-gray-600 text-sm font-semibold mb-8">Administrative Recording</h4>
                                            <div class="grid grid-cols-2 gap-x-12 gap-y-6">
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Recorded by</label><input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white bg-gray-100" value="{{ auth()->user()->name ?? '—' }}" readonly></div>
                                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Date</label><input type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white bg-gray-100" value="{{ now()->format('M d, Y') }}" readonly></div>
                                                <div class="col-span-2"><label class="block text-xs font-bold text-gray-700 mb-1">Remarks</label><textarea name="registration_remarks" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-data input-dcomc-focus bg-white" placeholder="N/A or additional notes">{{ old('registration_remarks') }}</textarea></div>
                                            </div>
                                        </section>
                                    </div>
                                </div>

                                <div class="p-6 border-t border-gray-100 flex justify-between items-center shrink-0 bg-gray-50/50">
                                    <button type="button" @click="currentTab = (currentTab === 'admin') ? 'education' : (currentTab === 'basic' ? 'address' : currentTab === 'address' ? 'guardian' : currentTab === 'guardian' ? 'education' : 'admin')" class="admin-btn-secondary">
                                        <span x-text="currentTab === 'admin' ? '← Back' : 'Next →'"></span>
                                    </button>
                                    <button type="submit" class="admin-btn-primary px-8 py-2.5">
                                        Save & Create Student Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
    </main>

    <script>
        function adminCreateStudentApp() {
            return {
                currentTab: 'basic',
                programOptions: @json(($programOptions ?? collect())->values()),
                majorsByProgram: @json($majorsByProgram ?? []),
                selectedProgram: '{{ old('course') }}',
                selectedMajor: '{{ old('major') }}',
                availableMajors: [],
                syncMajorsByProgram() {
                    const majors = this.majorsByProgram[this.selectedProgram] || [];
                    this.availableMajors = [...majors];
                    if (!this.availableMajors.includes(this.selectedMajor)) {
                        this.selectedMajor = '';
                    }
                },
                init() {
                    this.syncMajorsByProgram();
                }
            }
        }
    </script>
</body>
</html>
