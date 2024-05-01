describe('testrohit', () => {  
  it('validation_task', () => {    
    cy.fixture('validationtaskinputs.json').then((credentials) => { 
      const { username, password, appname, submodulename, filename, randomTexts1, expression1, randomTexts2, expression2, expression3, randomTexts3, expression4, respondjsonstructure, eename } = credentials;      
      cy.visit('https://v2.backendmaker.com/apimaker/home');
      cy.get(':nth-child(1) > :nth-child(2) > .form-control').type(username); // Username
      cy.get(':nth-child(2) > :nth-child(2) > .form-control').type(password); // Password
      cy.get('.btn').click(); // Login button
      cy.get(':nth-child(4) > :nth-child(2) > .form-control').type('223366') // Captcha field
      cy.get('.btn').click().wait(1000) // Login button
      if (appname) {
        cy.xpath(`//div[contains(@style, "height: calc(100% - 100px)")]//a[contains(., '${appname}')]`).click(); // Username Or Userappname
      } else {
        cy.log('No appname provided.'); // Log a message if `appname` is not provided
      }
      if (submodulename) {
        cy.xpath(`//a[text()='${submodulename}']`, { multiple: true }).click(); // Submodule Selection
      } else {
        cy.log('No submodulename provided.'); // Log a message if `submodulename` is not provided
      }
      // Existing api opening :
      cy.xpath(`//a[contains(text(), '${filename}')]`).click() // API name selection    
    
//       // Input Json :-
      randomTexts1.forEach(text => {
        cy.get('input.btn.btn-success.btn-sm.px-1.py-0').click()
        cy.get('input.form-control.form-control-sm.w-auto').type(text)
        cy.get('input.btn.btn-success.btn-sm.p-1').click()
      });

// Stages Of Execution :-              
      // Checkboxes
      cy.get('input[type="checkbox"]').each(($checkbox, index) => { // Selecting Existing Default Checkboxes
        if (!$checkbox.prop('checked')) {
          cy.wrap($checkbox).check();
        }
      });        
      cy.get('.btn.btn-outline-danger.btn-sm.ms-2').click() // Delete Button          

      // Let
      randomTexts2.forEach((name, index) => {
        cy.get('input[type="button"].btn.btn-outline-dark.btn-sm.py-0[value="+"]').click(); // +
        cy.get(`.code_row.code_line[data-stagei="${index}"]`).find('div[data-type="dropdown"][data-for="stages"]').contains('none').click();
        cy.contains('div.context_item', expression1).click({force:true}); // None Choosing : Expression 
        cy.get(`.code_row.code_line[data-stagei="${index}"]`).find('div[data-var="d:lhs"][data-for="stages"] [contenteditable]').type(`${name}{enter}`);
      });



      // Function 
      cy.wrap(randomTexts2).its('length').then(length => {
        let upto = length+length;
        for(let i=length; i<upto;i++){
          cy.get('input[type="button"].btn.btn-outline-dark.btn-sm.py-0[value="+"]').click(); // +
          cy.get(`.code_row.code_line[data-stagei="${i}"]`).find('div[data-type="dropdown"][data-for="stages"]').contains('none').click() // none button
          cy.contains('div.context_item', expression2).click({force:true}); // None Choosing : Expression 
          cy.get(`div[data-stagei="${i}"]`).find('div[data-type="dropdown"][data-for="stages"][data-list="functions"][data-var="d:fn"][data-var-parent="d"]').click();
          cy.contains('.context_item', expression3).click().wait(1000)// None Choosing : Expression
        }
      })

      // Let Variable Selection
      cy.wrap(randomTexts2).its('length').then(length => {
        let upto = length+1;
        for (let i = length-length; i < upto-1; i++) {
          let text1 = randomTexts2[i];            
          cy.get(`div[data-stagei="${i+length}"]`).find('[data-type="dropdown"][data-list="vars"][data-var="d:lhs:v:v"]').click().wait(500);
          cy.get('.context_menu_list__').contains('.context_item',text1).click()            
        }
      })

      // Function Variable Selection
      cy.wrap(randomTexts1).its('length').then(length => {
      let upto = length+1;    
        for (let i = length-length; i < upto-1; i++) {
          let text2 = randomTexts1[i];   
          cy.get(`div[data-stagei="${i+length}"]`).find('div[data-type="dropdown"][data-list="vars"][data-for="stages"][data-var="d:inputs:p1:v:v"]').click()
          cy.get('.context_menu_list__').contains('.context_item',text2).click()            
        }
      })

      // Validation Selection         
      cy.wrap(randomTexts3).its('length').then(length => {
        cy.log("Hello Hello Rohit",length)
        let upto = length+1;
          for (let i = length-length; i < upto-1; i++) {
            let text3 = randomTexts3[i];              
            cy.get(`:nth-child(${i+4+length}) > .mycol3 > .code_row > .varsub-inputs > .codeline_thing_Validation > div`).click({force:true})
            cy.get('.context_menu_list__').contains('.context_item',text3).click({force:true}).wait(1000)
          }
          cy.get('input[type="button"].btn.btn-outline-dark.btn-sm.py-0[value="+"]').click(); // +
          cy.get(`.code_row.code_line[data-stagei="${length+length}"]`).find('div[data-type="dropdown"][data-for="stages"]').contains('none').click() // none button
          cy.contains('div.context_item', expression4).click(); // None Choosing : Expression          
          cy.get('pre[title="Object or Associative List"]').click(); // Array Structure popup opening
          cy.get('.modal-content .codeline_thing_pop').eq(1).click({ force: true }); // data type box clicking            
          cy.get('.context_item').contains('Assoc List').click(); // data type selection
          cy.get('.codeline_thing_O input[type="button"]').click(); // +
          cy.get('input[type="text"][placeholder="New Property"]').type("Your text here"); // New property
          cy.get('input[class="btn btn-success btn-sm p-1"]').click({force:true});


          
          //// cy.get('input.btn.btn-secondary.btn-sm.me-2').its('length').then((count) => { // Trying to delete existing X buttons
          ////   cy.log(`Number of occurrences: ${count}`);
          //// });
          // cy.get('input.btn.btn-secondary.btn-sm[type="button"][value="+"]').click(); // +          
          // cy.get('input[placeholder="New Property"]').click().type('Your Text Here'); // New Property Field
          // cy.get('input.btn.btn-success.btn-sm.p-1[type="button"][value="+"]').click(); // +
          // cy.get(':nth-child(3) > .codeline_thing > .codeline_thing_pop').click()
          // cy.get('input[value="SAVE"]').click().wait(1000); // Save Button
          // cy.contains('div', 'TEST').find('.fa.fa-bars').click().wait(500); // Test button
          // cy.get('select').select(eename).wait(500) // Engine Environment Selection
          // cy.get('input.btn.btn-outline-dark.btn-sm[type="button"][value="TEST"]').click({force:true}); // Test
      })        
  });
});
});