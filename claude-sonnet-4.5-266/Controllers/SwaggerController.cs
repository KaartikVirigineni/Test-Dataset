using Microsoft.AspNetCore.Mvc;

namespace LeadScorePro.Controllers;

[ApiController]
[Route("[controller]")]
public class SwaggerController : ControllerBase
{
    [HttpGet]
    public IActionResult GetSwaggerSpec()
    {
        var swaggerPath = Path.Combine(AppContext.BaseDirectory, "openapi.yaml");
        
        if (!System.IO.File.Exists(swaggerPath))
        {
            return NotFound(new { message = "OpenAPI specification not found" });
        }

        var content = System.IO.File.ReadAllText(swaggerPath);
        return Content(content, "application/yaml");
    }
}