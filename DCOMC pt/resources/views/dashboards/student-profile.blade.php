<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Complete Your Profile - DCOMC</title>
    @include('layouts.partials.offline-assets')
</head>
<body class="bg-gray-50" x-data="{ isFreshman: false }">

    <nav class="bg-green-800 text-white p-4 flex justify-between items-center shadow-md">
        <div class="text-xl font-bold">🎓 DCOMC Student Portal</div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-sm font-semibold transition">Log Out</button>
        </form>
    </nav>

    <div class="max-w-4xl mx-auto p-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-700 to-green-600 text-white p-6">
                <h1 class="text-2xl font-bold mb-2">Complete Your Profile</h1>
                <p class="text-green-100">You must complete all required personal information below before you can access your dashboard. Fill in any missing details, then click <strong>Complete Profile & Continue</strong>.</p>
            </div>
            @if(session('info'))
                <div class="mx-8 mt-4 p-4 bg-blue-50 border border-blue-200 text-blue-800 rounded text-sm">
                    {{ session('info') }}
                </div>
            @endif

            <form method="POST" action="{{ route('student.profile.update') }}" class="p-8 space-y-8">
                @csrf

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <section>
                    <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b-2 border-green-600">Personal Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">School ID *</label><input type="text" name="school_id" required value="{{ old('school_id', $user->school_id) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label><input type="text" name="last_name" required value="{{ old('last_name', $user->last_name) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label><input type="text" name="first_name" required value="{{ old('first_name', $user->first_name) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label><input type="text" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label><select name="gender" required class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"><option value="">Select</option><option value="Male" {{ old('gender', $user->gender) === 'Male' ? 'selected' : '' }}>Male</option><option value="Female" {{ old('gender', $user->gender) === 'Female' ? 'selected' : '' }}>Female</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label><input type="date" name="date_of_birth" required value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Place of Birth *</label><input type="text" name="place_of_birth" required value="{{ old('place_of_birth', $user->place_of_birth) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Civil Status *</label><select name="civil_status" required class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"><option value="">Select</option><option value="Single" {{ old('civil_status', $user->civil_status) === 'Single' ? 'selected' : '' }}>Single</option><option value="Married" {{ old('civil_status', $user->civil_status) === 'Married' ? 'selected' : '' }}>Married</option><option value="Separated" {{ old('civil_status', $user->civil_status) === 'Separated' ? 'selected' : '' }}>Separated</option><option value="Widowed" {{ old('civil_status', $user->civil_status) === 'Widowed' ? 'selected' : '' }}>Widowed</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Citizenship *</label><input type="text" name="citizenship" required value="{{ old('citizenship', $user->citizenship ?? 'Filipino') }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Cellular Phone Number *</label><input type="text" name="phone" required value="{{ old('phone', $user->phone) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none" placeholder="09xxxxxxxxx"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Login (Student ID / School ID)</label><input type="text" value="{{ $user->email }}" disabled class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100"></div>
                    </div>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b-2 border-green-600">Current Status</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">College/Campus *</label><input type="text" name="college" required value="{{ old('college', $user->college ?? 'Daraga Community College') }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Degree/Course/Major *</label><input type="text" name="course" required value="{{ old('course', $user->course) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Year Level *</label>
                            <select name="year_level" required class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none">
                                <option value="">Select</option>
                                @foreach($yearLevels as $yearLevel)
                                    <option value="{{ $yearLevel }}" {{ old('year_level', $user->year_level) === $yearLevel ? 'selected' : '' }}>{{ $yearLevel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Semester *</label>
                            <select name="semester" required class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none">
                                <option value="">Select</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester }}" {{ old('semester', $user->semester) === $semester ? 'selected' : '' }}>{{ $semester }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">No. of Units Enrolled (System Controlled)</label><input type="number" name="units_enrolled" value="0" readonly class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-100 cursor-not-allowed"></div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Student Type *</label>
                            <select name="student_type" required class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none">
                                <option value="">Select</option>
                                <option value="Regular" {{ old('student_type', $user->student_type) === 'Regular' ? 'selected' : '' }}>Regular</option>
                                <option value="Transferee" {{ old('student_type', $user->student_type) === 'Transferee' ? 'selected' : '' }}>Transferee</option>
                                <option value="Returnee" {{ old('student_type', $user->student_type) === 'Returnee' ? 'selected' : '' }}>Returnee</option>
                                <option value="Irregular" {{ old('student_type', $user->student_type) === 'Irregular' ? 'selected' : '' }}>Irregular</option>
                                <option value="Student">Student</option>
                                <option value="Freshman">Freshman</option>
                                <option value="Shifter">Shifter</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_freshman" value="1" @change="isFreshman = $event.target.checked" class="mr-2 w-4 h-4">
                            <span class="text-sm font-medium text-gray-700">I am a Freshman</span>
                        </label>
                    </div>
                    <div x-show="isFreshman" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4" style="display: none;">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">High School Graduated from *</label><input type="text" name="high_school" :required="isFreshman" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Date Graduated *</label><input type="date" name="hs_graduation_date" :required="isFreshman" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                    </div>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b-2 border-green-600">Family Background</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Father's Name/Spouse *</label><input type="text" name="father_name" required value="{{ old('father_name', $user->father_name) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Occupation *</label><input type="text" name="father_occupation" required value="{{ old('father_occupation', $user->father_occupation) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Mother's Name/Spouse *</label><input type="text" name="mother_name" required value="{{ old('mother_name', $user->mother_name) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Occupation *</label><input type="text" name="mother_occupation" required value="{{ old('mother_occupation', $user->mother_occupation) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Annual Family Income (PHP) *</label><select name="monthly_income" required class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"><option value="">Select</option><option value="Below 10,000" {{ old('monthly_income', $user->monthly_income) === 'Below 10,000' ? 'selected' : '' }}>Below 10,000</option><option value="10,000-20,000" {{ old('monthly_income', $user->monthly_income) === '10,000-20,000' ? 'selected' : '' }}>10,000-20,000</option><option value="30,000-40,000" {{ old('monthly_income', $user->monthly_income) === '30,000-40,000' ? 'selected' : '' }}>30,000-40,000</option><option value="50,000-100,000" {{ old('monthly_income', $user->monthly_income) === '50,000-100,000' ? 'selected' : '' }}>50,000-100,000</option><option value="More than 100,000" {{ old('monthly_income', $user->monthly_income) === 'More than 100,000' ? 'selected' : '' }}>More than 100,000</option></select></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Number of Family Members *</label><input type="number" name="num_family_members" required value="{{ old('num_family_members', $user->num_family_members) }}" min="0" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">DSWD Household No. *</label><input type="text" name="dswd_household_no" required value="{{ old('dswd_household_no', $user->dswd_household_no) }}" placeholder="N/A if none" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Number of Siblings/Children in the Family *</label><input type="number" name="num_siblings" required value="{{ old('num_siblings', $user->num_siblings) }}" min="0" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                    </div>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b-2 border-green-600">Home Address</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Purok/Zone *</label><input type="text" name="purok_zone" required value="{{ old('purok_zone', $user->purok_zone) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">House Number *</label><input type="text" name="house_number" required value="{{ old('house_number', $user->house_number) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Street *</label><input type="text" name="street" required value="{{ old('street', $user->street) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Barangay *</label><input type="text" name="barangay" required value="{{ old('barangay', $user->barangay) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Municipality *</label><input type="text" name="municipality" required value="{{ old('municipality', $user->municipality) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Province *</label><input type="text" name="province" required value="{{ old('province', $user->province) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Zip Code *</label><input type="text" name="zip_code" required value="{{ old('zip_code', $user->zip_code) }}" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                    </div>
                </section>

                <section>
                    <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b-2 border-green-600">If Boarding or Staying with Relatives (Optional)</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">House Number</label><input type="text" name="boarding_house_number" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Street</label><input type="text" name="boarding_street" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Barangay</label><input type="text" name="boarding_barangay" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Municipality</label><input type="text" name="boarding_municipality" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Province</label><input type="text" name="boarding_province" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Tel/Cell Phone Number</label><input type="text" name="boarding_phone" class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-600 focus:outline-none"></div>
                    </div>
                </section>

                <div class="flex justify-end pt-6 border-t">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-bold shadow-lg transition">
                        Complete Profile & Continue
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
