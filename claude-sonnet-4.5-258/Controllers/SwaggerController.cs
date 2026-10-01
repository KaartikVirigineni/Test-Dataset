using Microsoft.AspNetCore.Mvc;

namespace ExperimentHub.Controllers;

[ApiController]
public class SwaggerController : ControllerBase
{
    [HttpGet("swagger")]
    public IActionResult GetSwagger()
    {
        var yamlPath = Path.Combine(Directory.GetCurrentDirectory(), "openapi.yaml");
        if (!System.IO.File.Exists(yamlPath))
            return NotFound();
            
        var yaml = System.IO.File.ReadAllText(yamlPath);
        return Content(yaml, "application/yaml");
    }
}