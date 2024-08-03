let form = document.getElementById('regForm'),
	loginForm = document.getElementById('loginForm'),
  csrfToken = (document.querySelector('[name="_token"]') && document.querySelector('[name="_token"]').value || ''),
	pwdd,excelData = [];
	(loginForm && loginForm.addEventListener("submit",async function(e) {
      e.preventDefault();
      if (!$(this).valid()) return false;
      if (!validate2()) return false;
      const data = new FormData(this);
      let formStatus = false;
      data.delete('password');
      data.append('password',pwdd)
      const action = this.action;
      const form = this;
      const button = $('button',this)[0];
      button.setAttribute('disabled',true);
      const btnText = button.textContent;
      try{
        //button.textContent = 'Please Wait...';
        startLoadings(button);
        $('input,select,textarea').attr('disabled',true);
        const res = await makeHttpRequest(action,'POST',data);
        if(res.success) {
        	formStatus = true;
        	toastr.success(res.success);
        }
        if (res.validationError) {
          Object.keys(res.validationError).forEach(message => {
            toastr.error(res.validationError[message]);
          })
        }

        if (res.error) toastr.error(res.error);

        if(res.captchaReload) {
        	$('.bi-arrow-clockwise').trigger('click');
        	$('[name=captcha]').val('');
        }

        if (res._token) $('input[name="_token"]').val(res._token);

        if (res.reloadReq) {
          setTimeout(() => {
            window.location.reload();
          },1000);
        }

      }catch(error){
        $('input,select,textarea').removeAttr('disabled');
        toastr.error(error)
      }
      if(!formStatus)
      	$('input,select,textarea').removeAttr('disabled');
      /*button.textContent = btnText;
      button.removeAttribute('disabled');*/
      console.log(!formStatus)
      stopLoadings(button,btnText,formStatus);
    })),
(form && (form.addEventListener("submit",async function(e) {
	e.preventDefault();
	if(!$('#regForm').valid()) return false;
  if (!excelData.length) {
    alert('Excel File is not loaded yet!!');
    return false;
  }
	$(document).find('.validationError').remove();	
	$('select,textarea,input').removeClass('border-danger');
	const button = this.querySelector('[type=submit]');
	const forms = this;
	const buttonText = button.innerText;
  startLoadings(button);
  await delay(1000);
	const formData = new FormData(form);
  form && $('select,input,textarea').attr('disabled',true);
	var url = this.action;
  
	try{	
    const jUrl = '/fetch-job-id';
    const getJobId = await makeHttpRequest(jUrl,'post',formData);
    // console.log(getJobId);
    var emails = chunkArray(excelData,10);
    emails = emails.map(ele => {
      return {emails:ele,jobId:getJobId.job_id};
    });
    console.log(emails);
    for (const ele of emails) {

      try {

        const res = await makeHttpRequest(url,'post',JSON.stringify(ele),true);
        //const parllerJob = makeHttpRequest('/cmd/queue-work-paraller','GET','');
        /* console.log(parllerJob);*/
        if ( res.success ) {

          if(res.sweetAlert){
            try{
              Swal.fire({
                title: "Successful",
                text: res.success,
                icon: "success",
                allowOutsideClick: false
              }).then(async (result) => {
                  if (result.isConfirmed) {
                    res.redirectConfirmation && await confirmation(res);
                    window.scrollTo({
                              top: 10,
                              // behavior: 'smooth'
                          });
                      if (res.redirect) {
                        toastr.success('Redirecting...');
                        setTimeout(()=>{window.location = res.redirect},1500);
                      }
                  }
              });
            }catch(error){
              alert(error)
            }
          }else{
            toastr.success(res.success);
          }
          
          if (res.reloadReq) {
            window.scrollTo({
                  top: 0,
                  behavior: 'smooth'
              });
            setTimeout(() => {
              window.location.reload();
            },1000)
          }
          //forms.reset();
        }

        if( res.error ){
            toastr.error(res.error);
        }

        if (res.validationError) {
          Object.keys(res.validationError).forEach(message => {
            toastr.error(res.validationError[message]);
          })
          
        }


      }catch(error){

        toastr.error(error);

      } 

      



    }
    stopLoadings(button,buttonText);
		//const res = await makeHttpRequest(url,'post',formData);
		/*if ( res.success ) {
			
			button.removeAttribute('disabled');
			button.innerText = buttonText;

			if(res.sweetAlert){
				try{
					Swal.fire({
					  title: "Successful",
					  text: res.success,
					  icon: "success",
					  allowOutsideClick: false
					}).then(async (result) => {
					    if (result.isConfirmed) {
                res.redirectConfirmation && await confirmation(res);
					    	window.scrollTo({
			                    top: 10,
			                    // behavior: 'smooth'
			                });
					        if (res.redirect) {
                    toastr.success('Redirecting...');
                    setTimeout(()=>{window.location = res.redirect},1500);
                  }
					    }
					});
				}catch(error){
					alert(error)
				}
			}else{
        toastr.success(res.success);
      }
			
			if (res.reloadReq) {
				window.scrollTo({
			        top: 0,
			        behavior: 'smooth'
			    });
				setTimeout(() => {
					window.location.reload();
				},1000)
			}
			forms.reset();
		} */

		/*if( res.error ){
			//Object.keys(res.error).forEach(message => {
				toastr.error(res.error);
			//})
			
			button.removeAttribute('disabled');
			button.innerText = buttonText;
		}*/

	/*	if (res.validationError) {
			Object.keys(res.validationError).forEach(message => {
				var span = document.createElement('span');
				span.style.fontSize = '12px';
				span.style.fontWeight = 'bold';
				span.classList.add('text-danger');
				span.classList.add('validationError');
				span.textContent = res.validationError[message];
				$(`[name="${message}"]`).addClass('border-danger');
				$(span).insertAfter($(`[name="${message}"]`)[0])
				//toastr.error(res.validationError[message]);
			})
			toastr.error('Please Read the Form Carefully for Errors!');
			
		}

    if (res.redirect) {
      toastr.success('Redirecting...');
      setTimeout(()=>{window.location = res.redirect},1500);
    }

		if (res.csrfToken) $('input[type=hidden]').val(res.csrfToken);*/
	}catch(error){
		toastr.error(error)
		/*button.removeAttribute('disabled');
		button.innerText = buttonText;*/
	}

	stopLoadings(button,buttonText);
})));


/*async function makeHttpRequest(url,method,data){
	const res = await fetch(url, {
		method: method,
		body: data
	})

	if (!res.ok) {
		console.log(res);
      throw new Error(res.statusText);
    }

     try {
        return await res.json();
    } catch (error) {

        let resText;
        try {
            resText = await res.text();
        } catch (textError) {
        	console.log(textError)
            throw new Error(textError);
        }
        throw new Error(resText ? resText : 'Invalid JSON response');
    }
} */

async function makeHttpRequest(url, method, data, headers=false) {
	let res,
	resText,
  config;

  config = headers ? {method: method,body: data,headers: {'X-CSRF-TOKEN': csrfToken}} : {method: method,body: data};
	try {
		
		if(method.toLowerCase() == 'post'){
			res = await fetch(url, config);
		}else{
			res = await fetch(url);
		}
	    
	    if (!res.ok) {
	        console.log(res);
	        throw new Error(res.statusText);
	    }

        return await res.json();
    } catch (error) {
    
        try {
            resText = await res.text(); // Read the text from the cloned response
        } catch (textError) {
        	console.log(textError)
            throw new Error("Error getting response text:", textError);
        }
        console.log(resText.slice(0,20));
        throw new Error(removeHtmlTags(resText.slice(0,500)));
    }
}

function removeHtmlTags(text) {
   /* // Regular expression to match HTML tags
    const htmlRegex = /<[^>]*>/g;
    // Replace HTML tags with an empty string
    return text.replace(htmlRegex, '');*/
    // Remove HTML tags
    text = text.replace(/<[^>]+>/g, '');
    
    // Remove HTML comments
    text = text.replace(/<!--[\s\S]*?-->/g, '');
    text = text.replace(/<!--/g, '');
    return text;
}

function validate2() {
  const enKey = $('input[name="_token"]').val();
  //alert('sdf');
  if (enKey != "") {
      let pwdObj = password;
      let hashObj = new jsSHA("SHA-512", "TEXT", {
          numRounds: 1
      });
      hashObj.update(pwdObj.value);
      let hash = hashObj.getHash("HEX");
      console.log(hash,'first hash');
      let hmacObj = new jsSHA("SHA-512", "TEXT");
      hmacObj.setHMACKey(enKey, "TEXT");
      hmacObj.update(hash);
      pwdd = hmacObj.getHMAC("HEX");
      return true;
  } else {
      return false;
  }
}


//for input type number icon remove

$(document).ready(function() {
    $(document).on("keypress",".num",function() {
        if ($(this).val().length == $(this).attr("maxlength")) {
            return false;
        }
    });

    $(document).on("keypress",".num[type=number]",function(event) {
    	return event.keyCode === 8 || event.charCode >= 48 && event.charCode <= 57; 
    })

    $(document).on("keypress",".allowDot[type=number]",function(event) {
      return event.charCode === 46 || (event.charCode >= 48 && event.charCode <= 57); 
    })

    $('.alphaSpace').keypress(function() {
    	return (event.keyCode >= 65 && event.keyCode <= 90) || (event.keyCode >= 97 && event.keyCode <= 122) || event.keyCode === 32;
    })


    form && $(form).validate();
    loginForm && $(loginForm).validate();


    $(document).on("input",".currency-input",function() {
      formatCurrency(this);
    })
    
});

function startLoadings(thi,text='Please Wait...') {
  
  thi.setAttribute('disabled',true);
  var loader = document.createElement('span');
  $(loader).attr({
    'class' : 'spinner-border spinner-border-sm',
    'role' : 'status',
    'aria-hidden' : 'true'
  });
  thi.innerHTML = $(loader)[0].outerHTML+'  '+ text;

}

function stopLoadings(thi,text,disabled=false) {
  form && $('select,input,textarea').removeAttr('disabled');
  thi.disabled = disabled;
  thi.innerHTML = text;
}

function delay(sec){
  return new Promise(resolve => setTimeout(resolve,sec));
}

// HTML entity encoding function
function htmlEncode(text) {
    // Define the regular expressions for HTML entity encoding
    var ampRegex = /&/g;
    var gtRegex = />/g;
    var ltRegex = /</g;

    // Perform HTML entity encoding
    return String(text).replace(ampRegex, '&amp;').replace(gtRegex, '&gt;').replace(ltRegex, '&lt;');
}

function htmlRemove(text) {
    // Define the regular expressions for HTML entity decoding
    var ampRegex = /&amp;/g;
    var gtRegex = /&gt;/g;
    var ltRegex = /&lt;/g;

    // Perform HTML entity decoding
    return String(text).replace(ampRegex, '').replace(gtRegex, '').replace(ltRegex, '');
}

function htmlDecode(text) {
    // Define the regular expressions for HTML entity decoding
    var ampRegex = /&amp;/g;
    var gtRegex = /&gt;/g;
    var ltRegex = /&lt;/g;
    var quot = /&quot;/g;
    // Perform HTML entity decoding
    return String(text).replace(ampRegex, '&').replace(gtRegex, '>').replace(ltRegex, '<').replace(quot,'"');
}

function removeHtmlTags(text) {
    // Define the regular expression for HTML tags
    var htmlRegex = /<[^>]*>/g;
    
    // Remove HTML tags from the string
    return text.replace(htmlRegex, '');
}

function convertDateInDays(date){
  var dateString = date;

  // Create a Date object from the given date string
  var givenDate = new Date(dateString);

  // Get the current date
  var currentDate = new Date();

  // Calculate the difference in milliseconds between the two dates
  var differenceInMilliseconds = currentDate - givenDate;

  // Convert the difference to days
  var differenceInDays = Math.floor(differenceInMilliseconds / (1000 * 60 * 60 * 24));

  return differenceInDays;
}

function capitalizeFirstLetter(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

async function confirmation(res) {
  Swal.fire({
    title: res.redirectMessage,
    text: res.redirectConfirmation,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, Proceed!",
    allowOutsideClick: false
  }).then((result) => {
    if (result.isConfirmed) {
      toastr.success('Redirecting...');
      setTimeout(() => {
        window.location = res.redirectYesUrl;
      },1000);
    }else{
      window.location = res.redirectNoUrl;
    }
  });  
}

function convertStringToOriginalType(input) {
    // Check if input is a string
    if (typeof input === 'string' || input instanceof String) {
        try {
            // Attempt to parse the string
            const parsed = JSON.parse(input);

            return parsed;
        } catch (e) {
            // If parsing fails, it's a regular string
            return input;
        }
    }

    // If it's not a string, return undefined or handle accordingly
    return input;
} 

function mb_strimwidth(str, start, width, trimmarker = '...') {
    let result = '';
    let len = 0;
    
    for (let i = start; i < str.length; i++) {
        let char = str.charAt(i);
        let charCode = char.charCodeAt(0);
        
        // Increase the length count based on character type
        if (charCode >= 0 && charCode <= 127) {
            len += 1; // ASCII characters
        } else {
            len += 2; // Multibyte characters
        }
        
        // Break if the desired width is reached
        if (len > width) {
            result += trimmarker;
            break;
        }
        
        result += char;
    }
    
    return result;
}

function formatCurrency(ele){
  //alert(232)
  
  let value = ele.value.replaceAll(',','').replaceAll('₹ ','');
  let newValue = '₹ '+(+value).toLocaleString('en-IN');
  console.log(newValue);
  ele.value = newValue;
}


document.getElementById('excel').addEventListener('change', function(event) {
  // Get the file from the input
  var file = event.target.files[0];
  var reader = new FileReader();

  // Show the loading spinner
  document.body.classList.add('loading');

  // Define what happens when the file is read
  reader.onload = function(e) {
    // Get the data from the file
    var data = e.target.result;

    try {
      // Read the data as a binary string
      var workbook = XLSX.read(data, { type: 'binary' });

      // Get the first sheet
      var firstSheet = workbook.Sheets[workbook.SheetNames[0]];

      // Convert the sheet to JSON
      var jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
      //excelData = jsonData;
      // Display the JSON data in an HTML table
      //displayData(jsonData);
      jsonData = jsonData.map(item => item[0]);
      jsonData.shift();
      console.log(jsonData);
      excelData = jsonData;

    } catch (error) {
      excelData = jsonData;
      console.error('Error processing file:', error);
    } finally {
      excelData = jsonData;
      // Hide the loading spinner
      document.body.classList.remove('loading');
    }
  };

  // Define what happens in case of an error
  reader.onerror = function() {
    console.error('Error reading file');
    excelData = jsonData;
    // Hide the loading spinner
    document.body.classList.remove('loading');
  };

  // Read the file as a binary string
  reader.readAsBinaryString(file);
});


function chunkArray(array, chunkSize) {
  const chunks = [];
  for (let i = 0; i < array.length; i += chunkSize) {
    chunks.push(array.slice(i, i + chunkSize));
  }
  return chunks;
}


