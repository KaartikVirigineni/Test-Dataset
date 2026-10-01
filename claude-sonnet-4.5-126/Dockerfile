FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY GeoMapper.sln .
COPY GeoMapper/GeoMapper.csproj GeoMapper/
RUN dotnet restore

COPY . .
WORKDIR /src/GeoMapper
RUN dotnet publish -c Release -o /app/publish

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS runtime
WORKDIR /app
COPY --from=build /app/publish .
COPY openapi.yaml .

ENV ASPNETCORE_URLS=http://+:8000
EXPOSE 8000

ENTRYPOINT ["dotnet", "GeoMapper.dll"]