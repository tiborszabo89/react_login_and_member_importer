document.addEventListener('DOMContentLoaded', () => {
  const react_form = document.querySelector('.react_login');
  if(react_form) {
    react_form.style.display = "none"
  }

  function renderModal(element) {
      const modal = document.createElement('div')
      modal.classList.add('modal-custom')
      const child = document.createElement('div')
      child.classList.add('child')
      modal.appendChild(child)
      document.body.appendChild(modal)
      child.appendChild(react_form)
      react_form.style.display = "block"
      modal.addEventListener('click', event => {
          if (event.target.className === 'modal-custom') {
            removeModal()
          }
        })
        modal.addEventListener('click', event => {
          if (event.target.className === 'closex') {
            removeModal()
          }
        })

  }
    function removeModal(){
      const modal = document.querySelector('.modal-custom')
      if (modal) {
        modal.remove()
      }
    }
  const reactPopBtn = document.querySelectorAll('.react-login-pop');
  if(reactPopBtn) {
    Array.prototype.forEach.call(reactPopBtn, (rea) => {
      rea.addEventListener('click', () => {
        renderModal();
      })
    })
  }
})

let superbtn = document.querySelectorAll('.super-loader');
if(superbtn) {
  Array.prototype.forEach.call(superbtn, (supr) => {
    supr.addEventListener('click', function () {

      supr.classList.add('spin');
      supr.disabled = true;

      supr.form.firstElementChild.disabled = true;
    
      supr.classList.remove('spin');
      supr.disabled = false;
      supr.form.firstElementChild.disabled = false;
      
    }, false);
  })
}

