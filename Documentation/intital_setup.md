## Setup
**1. Create a new login page**
- Create a new page in module (note the UID) **Web → Page**
- Add content element: **Page Password** from **Forms** section
- Configure styling options (colors, logo) or use defaults
- Ensure the page is accessible

**2. Configure Site Settings**

**Backend method (recommended):**
- Go to **Site Management → Sites**
- Edit your site configuration
- Go to **Page protection** and set **Default login page** field to your login page

**YAML method:** Edit : `config/sites/[site-identifier]/config.yaml`
``` yaml
pagepassword_default_login_page: 't3://page?uid=xxx'
```
Replace `xxx` with your login page UID.

**3. Protect Pages**
- Edit target page → **Page protection** tab
- Check **Enable page password protection**
- Set password
- Optionally check **Extend to subpages** for tree protection

**4. Test & Troubleshoot**
- Clear all caches
- Visit protected page
- Verify redirection to login page works
- Test password authentication

## (optional) Configure a login page with a custom layout
Useful if you want to protect an entire site and don't want a header or footer to appear.
You can achieve this as follows:
1. **Register a new backend layout:**

```
mod {
  web_layout {
    BackendLayouts {
      pagepassword_login {
        title = Page password login
        config {
          backend_layout {
            colCount = 1
            rowCount = 1
            rows {
              1 {
                columns {
                  1 {
                    identifier = main
                    name = Form
                    colPos = 0
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
```
2. **Add TypoScript:**
```
[page["backend_layout"] == 'pagets__pagepassword_login']
  page >
  page = PAGE
  page.typeNum = 0
  page.includeCSS.reset = EXT:page_password/Resources/Public/assets/css/reset.min.css
  page {
    10 = CONTENT
    10 {
      table = tt_content
      select {
        orderBy = sorting
        where = {#colPos}=0
      }
    }
  }
[end]
```
3. Use the new backend layout under **Page properties** → **Appearance** → **Backend Layout**


## Troubleshooting
**Common issues:**
- Wrong UID in site configuration
- Login page not accessible
- Caches not cleared after changes
- The default styles should not conflict with those of your project. If this is the case, please open an issue.
