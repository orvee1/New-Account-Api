<x-app-layout>

<div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-4 print:grid-cols-2">
    <input 
        id="search" 
        type="search" 
        oninput="search()" 
        value="{{ request()->search }}" 
        class="block w-full px-3 py-2 border border-gray-400 rounded focus:outline-0" 
        placeholder="Search" 
    />

    <div class="w-full grid grid-cols-2 gap-2 items-center">
        <label for="requestPendingSearch" class="text-right">
            Request Pending:
        </label>
        <select oninput="search()"  id="requestPendingSearch"  class="block w-full px-3 py-2 border border-gray-400 rounded focus:outline-0" >
            <option value="">All</option>
            <option value="yes">Yes</option>
        </select>
    </div>
</div>

<hr class="my-3 print:hidden">

<div class="" id="dataContainer">
    <!-- data -->   
</div>

<script>
    function search(page = 1) {
        const searchElement = document.getElementById('search');;
        const requestPendingSearch = document.getElementById('requestPendingSearch');
        const dataContainer = document.getElementById('dataContainer');

        const search = searchElement?.value;
        const request_pending = requestPendingSearch?.value;

        dataContainer.innerHTML = `
            <div class="flex justify-center items-center h-40">
                <div class="text-3xl font-bold">
                    Loading <span class="animate-ping">...</span>
                </div>    
            </div> 
        `;

        setUrl(page, search, {});

        const params = new URLSearchParams({
            page,
            search,
            request_pending
        }).toString();

        fetch(`/admin/admin-device-log?${params}`, {
            method: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.text(); // Assuming response.data is HTML
        })
        .then(data => {
            dataContainer.innerHTML = data;
        })
        .catch(error => {
            console.error("Error:", error);
        });

    }

    search({{ request()->page }});

    function setUrl(page, search, filters) {
        const url = new URL(window.location.href);

        url.searchParams.set('page', page);
        
        for (const [key, value] of Object.entries(filters)) {
            url.searchParams.delete(key);

            if(value) {
                url.searchParams.set(key, value);
            }
        }
        
        url.searchParams.delete('search');
        if(search) {
            url.searchParams.set('search', search);
        }
        
        window.history.pushState({}, '', url.href);
    }

    function imageOnErrorHandler(imgElement) {
        imgElement.src = 'https://edudent-file.s3.ap-southeast-1.amazonaws.com/img/doc_male.jpg';
    }
</script>

</x-app-layout>