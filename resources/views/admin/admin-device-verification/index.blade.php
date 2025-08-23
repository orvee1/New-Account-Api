<x-app-layout>
<div class="container">
    <div class="row">
        <div class=" bg-white" style="margin: 10px auto; width: 65%;">
            <div class="panel_box w-100 rounded shadow-sm ">
                <div class="text-center py-4 bg-blue-100 rounded-t-lg">
                    <h2 class="text-xl font-semibold text-blue-900">My Admin Device Information</h2>
                </div>
            </div>
            <div class="panel-body">
                <div class="my-3 p-3 p-md-4 w-100 bg-white rounded shadow-sm" style="text-align: center; font-size: 18px;">
                    <h3>Verified Devices (Device and Browser)</h3>

                    @forelse($active_devices as $active_device)
                    <hr class="my-1 my-md-2" />
                    <div class="">
                        <div class="my-2" style="position: relative; display: inline-block;padding: 6px 12px; background: ccc; text-align: center; border: 1px solid #38BDF8; border-radius: 10px; font-size: 16px;">
                            <div style="display: flex; gap: 4px; align-items: center;">
                                @if($active_device->is_smart_phone)
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="36">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                </svg>
                                @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="32">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                                </svg>
                                @endif
                                <div>
                                    {{ $active_device->name ?? '' }}
                                </div>
                                @if($current_device->is_verified && $current_device->uuid == $active_device->uuid)
                                <span
                                    style="position: absolute; background: #38BDF8; width: 12px; height: 12px; border-radius: 50%; top: -4px; right: -4px;"
                                ></span>
                                <span
                                    class="animate-ping"
                                    style="position: absolute; background: #38BDF8; width: 12px; height: 12px; border-radius: 50%; top: -4px; right: -4px;"
                                ></span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <hr class="my-1 my-md-2" />
                    <div class="text-danger" style="color: #c01616; font-size: 16px;">
                        No verified device
                    </div>
                    @endforelse
                </div>

                <div class="my-3 p-3 p-md-4 w-100 bg-white rounded shadow-sm text-center" style="font-size: 18px;">
                    <h3>Current Device (Device and Browser)</h3>
                    <hr class="my-1 my-md-2" />
                    <div class="">
                        <div class="my-2" style="position: relative; display: inline-block;padding: 6px 12px; background: ccc; text-align: center; border: 1px solid #38BDF8; border-radius: 10px; font-size: 16px;">
                            <div style="display: flex; gap: 4px; align-items: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="32">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                                </svg>
                                <div>
                                    {{ $current_device->name }}
                                </div>
                            </div>
                            <span
                                style="position: absolute; background: #38BDF8; width: 12px; height: 12px; border-radius: 50%; top: -4px; right: -4px;"
                            ></span>
                            <span
                                class="animate-ping"
                                style="position: absolute; background: #38BDF8; width: 12px; height: 12px; border-radius: 50%; top: -4px; right: -4px;"
                            ></span>
                        </div>
                    </div>
                    
                    @if($current_device->verified_at && !$current_device->expired_at)
                        <div class="text-success" style="line-height: 1.5; color: #16a34a">
                            Your Current Device (Device and Browser) has been verified
                        </div>
                    @else
                        @if(Auth::user()->hasRole('Developer') || Auth::user()->hasRole('Administrator') || $count_active_devices < 1)
                        <div id="otp_div" style="margin-bottom: 10px;">
                            <div style="line-height: 1.5; color: #c01616; text-align:center; font-size: 18px; padding: 2px;">
                                Your Current Device (Device and Browser) has not been verified yet
                            </div>
                            
                            <div class="text-danger" style="margin: 2px auto; line-height: 1.5; color: #169ec0; text-align:center; font-size: 18px; padding: 5px; display:flex; gap: 5px; align-items:center; justify-content:center;">
                                <label for="check_otp" style="cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="check_otp" id="check_otp" value="1" style="width: 20px; height:20px;">
                                    <span style="font-size: 18px;">If you want to verify this browser.</span>
                                </label>
                            </div>
                            <div style="padding: 10px; text-align:center; font-size: 18px;">
                                <button id='send_otp_button' class="py-1 px-3 text-white rounded-md shadow-sm" style="background-color: #0f74c7b0" type="submit" disabled>
                                    Send OTP
                                </button>
                            </div>
                        </div>
                        @else
                            <div class="text-danger" style="line-height: 1.5; text-align:center; font-size: 18px;">
                                @if($current_device->request->reason ?? false)
                                <p style="color: #1682c0; font-weight: 600;">
                                    Request send successfully. </br>
                                    Please contact to the administrator for verification.
                                </p>
                                @else
                                <p style="color: #c01616; font-weight: 600;">
                                    If you want to verify more browser. </br>
                                    Write your reason and submit request to the administrator.

                                </p>
                                @endif
                            </div>
                            <form method="POST" action= "{{ route( 'admin-device-verification.store' ) }}" >
                                @csrf
                                <div class="form-group">
                                    <textarea 
                                        name="reason"
                                        class="form-control"
                                        type="text"
                                        required
                                        rows="5"
                                        id="reason"
                                        style="height: 130px; background-color: rgb(250, 242, 242); width:75%; border-radius: 10px; margin: 10px auto; padding: 10px; font-size: 16px;"
                                    >{{ $current_device->request->reason ?? "" }}</textarea>
                                </div>
                                <div class="text-center" style="margin: 0px auto;">
                                    <button id='submit' class="py-2 px-4 text-white rounded-md shadow-sm" type="submit" disabled>
                                        Submit Request
                                    </button>
                                </div>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const textarea = document.getElementById("reason");
        const submitButton = document.getElementById("submit");
        const initialValue = textarea?.value.trim();

        function updateButtonState() {
            if (!textarea) return;
            const currentValue = textarea.value.trim();
            const isChanged = currentValue !== initialValue;
            const isNotEmpty = currentValue.length > 0;

            submitButton.disabled = !(isChanged && isNotEmpty);
            submitButton.style.backgroundColor = (isChanged && isNotEmpty) ? "#16a34a" : '#0f74c7b0';
        }

        if (textarea) {
            updateButtonState();
            textarea.addEventListener("input", updateButtonState);
        }

        const checkOtp = document.getElementById("check_otp");
        const submitOtpButton = document.getElementById("send_otp_button");
        if(checkOtp){
            submitOtpButton.style.backgroundColor = (checkOtp.checked) ? "#0f74c7" : '#0f74c7b0';
            checkOtp.addEventListener("change", function(){
                if(checkOtp.checked){
                    submitOtpButton.disabled = false;
                    submitOtpButton.style.backgroundColor = "#0f74c7" ;
                }
                else{
                    submitOtpButton.disabled = true;
                    submitOtpButton.style.backgroundColor = '#0f74c7b0';
                }
            })
        }
        // Handle OTP button click
        const sendOtpButton = document.getElementById('send_otp_button');
        const otpDiv = document.getElementById('otp_div');
        if (sendOtpButton) {
            sendOtpButton.addEventListener("click", function () {
                sendOtpButton.disabled = true;
                sendOtpButton.innerText = "Sending...";

                fetch("{{ route('admin-device-verification.otp-send') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({})
                })
                .then(res => res.json())
                .then(data => {
                    
                    if (data.status) {
                        showOtpInput(data.message ?? 'OTP send successfully.');
                        otpDiv.innerHTML = '';
                    } else {
                        alert(data.message || "OTP send failed");
                    }
                })
                .catch(err => {
                    alert("Server error while sending OTP");
                })
                .finally(() => {
                    sendOtpButton.disabled = false;
                    sendOtpButton.innerText = "Send OTP";
                });
            });
        }

        function showOtpInput(message) {
            const container = document.createElement("div");
            container.innerHTML = `
                <div style="line-height: 1.5; color: #16a34a; text-align:center; font-size: 18px; padding: 2px;">
                    ${message}
                </div>
                <div class="text-center" style="padding: 10px;">
                    <input id="otp_code" type="text" maxlength="6" placeholder="Enter OTP Code"
                        style="padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 16px;">
                    <button id="verify_otp" class="py-2 px-4 text-white bg-green-500 hover:bg-green-700 rounded-md ml-2" style="background-color: #168ac0; border-radius: 5px;">
                        Verify OTP
                    </button>
                </div>
            `;
            document.querySelector(".panel-body").appendChild(container);

            document.getElementById("verify_otp").addEventListener("click", function () {
                const otpCode = document.getElementById("otp_code").value.trim();
                if (otpCode.length !== 5) {
                    alert("OTP must be 5 digits");
                    return;
                }

                fetch("{{ route('admin-device-verification.otp-store') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ otp: otpCode })
                })
                .then(res => res.json())
                .then(data => {
                    console.log(data);
                    if (data.status) {
                        alert("Device verified successfully.");
                        location.reload();
                    } else {
                        alert(data.message || "OTP verification failed.");
                    }
                })
                .catch(err => {
                    alert("Error verifying OTP");
                });
            });
        }
    });
</script>
</x-app-layout>