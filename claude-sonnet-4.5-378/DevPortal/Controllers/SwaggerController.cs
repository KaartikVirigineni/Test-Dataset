using Microsoft.AspNetCore.Mvc;

namespace DevPortal.Controllers;

[ApiController]
[Route("[controller]")]
public class SwaggerController : ControllerBase
{
    [HttpGet]
    public IActionResult GetSwaggerSpec()
    {
        var yamlPath = Path.Combine(Directory.GetCurrentDirectory(), "openapi.yaml");
        
        if (!System.IO.File.Exists(yamlPath))
        {
            return NotFound(new { message = "OpenAPI specification not found" });
        }

        var yaml = System.IO.File.ReadAllText(yamlPath);
        return Content(yaml, "application/yaml");
    }
}