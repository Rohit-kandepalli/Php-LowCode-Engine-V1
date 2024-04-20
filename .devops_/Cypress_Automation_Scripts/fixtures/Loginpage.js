class LoginPage
{
    setUsername(username)
    {
       cy.get('#txt_usr').type(username)
    }
    setPassword(password)
    {
        cy.get('#txt_pwd').type(password)
    }
    clicklogin()
    {
        cy.get('#forgot_show > .container-login100-form-btn > #submit_btn').click()
    }
    otp1(otp1)
    {
        cy.get('#otp1').type(otp1)
    }
    otp2(otp2)
    {
        cy.get('#otp2').type(otp2)
    }
    otp3(otp3)
    {
        cy.get('#otp3').type(otp3)
    }
    otp4(otp4)
    {
        cy.get('#otp4').type(otp4)
    }
    otp5(otp5)
    {
        cy.get('#otp5').type(otp5)
    }
    otp6(otp6)
    {
        cy.get('#otp6').type(otp6)
    }
    clicksubmit()
    {
        cy.get('#submit_btn2').click()
    }
    verifylogin()
    {
        cy.get('.text-primary').should('have.contain','Rohit K!')
    }
}

export default LoginPage;